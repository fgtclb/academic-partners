<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationChangedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

/**
 * The tiles of the partner map under the Content Security Policy TYPO3 sends once a site
 * enforces it (ACE-933).
 *
 * The map loads its tiles from the host of its tile URL, which `img-src` of the default
 * policy does not allow. The map allows that host on the page it is rendered on, for the
 * tile URL the site configures, both on the request that generates the page and on the
 * requests that are served from the page cache.
 *
 * The list is on `/home`, the map on `/map`, see `AcademicPartnersListFilterTest`.
 */
final class AcademicPartnersMapTileSourceTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const DEFAULT_TILE_SOURCE = 'https://*.tile.openstreetmap.org';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'features' => [
                    'security.frontend.enforceContentSecurityPolicy' => true,
                ],
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored, so the second request of a page is
                        // served from the cache the first one wrote.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        // The scopes of the policy are singletons that keep the site object of the first
        // request of the process, which may be one of another test with another policy.
        Scope::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersListFilter/partnerListAndMapPages.csv');
        $this->getConnectionPool()->getConnectionForTable('cache_pages')->truncate('cache_pages');
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');
    }

    protected function tearDown(): void
    {
        Scope::reset();
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theMapAllowsTheDefaultTileServerOnTheGeneratedAndOnTheCachedPage(): void
    {
        $this->setUpStaticTemplateSite();

        $generated = $this->imageSources('https://www.acme.com/map');
        $this->assertSame(1, $this->countCachedPages(), 'The first request generates and caches the page.');
        // The page cache carries the source with the page, which is what a cached request
        // restores before the map action runs.
        $this->assertStringContainsString('*.tile.openstreetmap.org', $this->cachedPolicyRegistryState());
        $cached = $this->imageSources('https://www.acme.com/map');
        $this->assertSame(1, $this->countCachedPages(), 'The second request is served from the page cache.');

        foreach (['generated' => $generated, 'cached' => $cached] as $page => $sources) {
            $this->assertContains(self::DEFAULT_TILE_SOURCE, $sources, $page);
            // Extended: what the default policy allows stays.
            $this->assertContains("'self'", $sources, $page);
            $this->assertContains('data:', $sources, $page);
        }
    }

    /**
     * The tile URL of the source is the one the map renders, merged as Extbase merges the
     * settings: the plugin TypoScript of the map over the plugin object of the content
     * element, over the plugin TypoScript of the extension, which the constant sets.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function tileUrlOverrides(): \Generator
    {
        $plugin = 'plugin.tx_academicpartners_map.settings.map.tileUrl = https://plugin.example.org/{z}/{x}/{y}.png';
        $element = 'tt_content.academicpartners_map.20.settings.map.tileUrl = https://{s}.element.example.org/{z}/{x}/{y}.png';
        yield 'the plugin TypoScript of the map' => [
            $plugin,
            'https://plugin.example.org/{z}/{x}/{y}.png',
            'https://plugin.example.org',
        ];
        yield 'the plugin object of the content element' => [
            $element,
            'https://{s}.element.example.org/{z}/{x}/{y}.png',
            'https://*.element.example.org',
        ];
        yield 'the plugin TypoScript of the map over the plugin object' => [
            $element . "\n" . $plugin,
            'https://plugin.example.org/{z}/{x}/{y}.png',
            'https://plugin.example.org',
        ];
    }

    #[DataProvider('tileUrlOverrides')]
    #[Test]
    public function theSourceFollowsTheTileUrlTheMapRenders(string $setup, string $tileUrl, string $source): void
    {
        $this->setUpStaticTemplateSite();
        $this->appendTypoScriptSetup($setup);

        $generated = $this->imageSources('https://www.acme.com/map');
        $cached = $this->imageSources('https://www.acme.com/map');
        $this->assertSame(1, $this->countCachedPages());

        $xpath = new \DOMXPath($this->parsedPage($this->renderFrontendPage('https://www.acme.com/map')));
        $maps = $xpath->query('//*[@id="map"]');
        $this->assertInstanceOf(\DOMNodeList::class, $maps);
        $map = $maps->item(0);
        $this->assertInstanceOf(\DOMElement::class, $map);
        $this->assertSame($tileUrl, $map->getAttribute('data-academic-partners-tile-url'));
        foreach (['generated' => $generated, 'cached' => $cached] as $page => $sources) {
            $this->assertContains($source, $sources, $page);
            $this->assertNotContains(self::DEFAULT_TILE_SOURCE, $sources, $page);
        }
    }

    /**
     * The content element registers the source while the page is generated. The map
     * action is not cacheable and renders after TYPO3 has prepared the policy of the
     * generated page, so without the data processor of the element only the page served
     * from the cache gets the source, from the action.
     */
    #[Test]
    public function withoutTheProcessorOnlyTheCachedPageAllowsTheTileServer(): void
    {
        $this->setUpStaticTemplateSite();
        $this->appendTypoScriptSetup('tt_content.academicpartners_map.dataProcessing.1790226400 >');

        $generated = $this->imageSources('https://www.acme.com/map');
        $cached = $this->imageSources('https://www.acme.com/map');
        $this->assertSame(1, $this->countCachedPages());

        $this->assertNotContains(self::DEFAULT_TILE_SOURCE, $generated);
        $this->assertContains(self::DEFAULT_TILE_SOURCE, $cached);
    }

    #[Test]
    public function theMapAllowsTheTileServerTheSiteConfigures(): void
    {
        $this->setUpSiteSetSite([
            'plugin.tx_academicpartners.map.tileUrl' => 'https://tiles.example.org:8443/{z}/{x}/{y}{r}.png',
        ]);

        $generated = $this->imageSources('https://www.acme.com/map');
        $cached = $this->imageSources('https://www.acme.com/map');
        $this->assertSame(1, $this->countCachedPages());

        foreach (['generated' => $generated, 'cached' => $cached] as $page => $sources) {
            $this->assertContains('https://tiles.example.org:8443', $sources, $page);
            $this->assertNotContains(self::DEFAULT_TILE_SOURCE, $sources, $page);
        }
    }

    /**
     * The registered source applies on top of the policy of the site, also where the site
     * inherits nothing of the default policy.
     */
    #[Test]
    public function aSiteThatInheritsNoDefaultPolicyGetsTheTileServerAsWell(): void
    {
        $this->setUpStaticTemplateSite();
        $this->writeSiteContentSecurityPolicy(<<<'YAML'
            inheritDefault: false
            mutations:
              - mode: set
                directive: 'default-src'
                sources:
                  - "'self'"
              - mode: set
                directive: 'img-src'
                sources:
                  - "'self'"
            YAML);

        foreach (['generated', 'cached'] as $page) {
            $sources = $this->imageSources('https://www.acme.com/map');
            $this->assertSame(["'self'", self::DEFAULT_TILE_SOURCE], $sources, $page);
        }
    }

    #[Test]
    public function aPageWithoutAMapAllowsNoTileServer(): void
    {
        $this->setUpStaticTemplateSite();

        foreach (['generated', 'cached'] as $page) {
            $sources = $this->imageSources('https://www.acme.com/home');
            $this->assertNotContains(self::DEFAULT_TILE_SOURCE, $sources, $page);
            $this->assertContains('data:', $sources, $page);
        }
        $this->assertSame(1, $this->countCachedPages());
    }

    /**
     * The sources of `img-src` the enforced policy of the page allows.
     *
     * Every request of a test runs in the one process of the test, and so do the
     * mutations a request registers for its own response in the `PolicyRegistry` of
     * TYPO3, a singleton. A request of a web server starts with an empty one.
     *
     * @return list<string>
     */
    private function imageSources(string $url): array
    {
        $this->get(PolicyRegistry::class)->setMutationsCollections();
        $response = $this->requestFrontendPage($url);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            $url === 'https://www.acme.com/map' ? 'id="map"' : 'class="academic-partners-list',
            (string)$response->getBody(),
        );

        return $this->contentSecurityPolicy($response)['img-src'] ?? [];
    }

    /**
     * The `csp.yaml` of the site, next to the `config.yaml` the set up wrote. The site
     * configuration is cached since that write, so the change is announced as the site
     * writer of TYPO3 announces its own, and the scopes of the policy are dropped, see
     * `setUp()`.
     */
    private function writeSiteContentSecurityPolicy(string $yaml): void
    {
        file_put_contents($this->instancePath . '/typo3conf/sites/acme/csp.yaml', $yaml . LF);
        $this->get(EventDispatcherInterface::class)->dispatch(new SiteConfigurationChangedEvent('acme'));
        Scope::reset();
    }

    /**
     * Appends setup to the template record the set up wrote for the root page.
     */
    private function appendTypoScriptSetup(string $setup): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $config = $connection->select(['config'], 'sys_template', ['pid' => 1])->fetchOne();
        $this->assertIsString($config);
        $connection->update('sys_template', ['config' => $config . LF . $setup . LF], ['pid' => 1]);
    }

    /**
     * The mutation collections of the `PolicyRegistry` the one cached page carries, as
     * TYPO3 stored them, read through the page cache, which compresses its entries.
     */
    private function cachedPolicyRegistryState(): string
    {
        $identifier = $this->getConnectionPool()->getConnectionForTable('cache_pages')
            ->select(['identifier'], 'cache_pages')->fetchOne();
        $this->assertIsString($identifier);
        $entry = $this->get(CacheManager::class)->getCache('pages')->get($identifier);
        $this->assertIsArray($entry);
        $state = $entry['metaDataState']['PolicyRegistry::$mutationCollections'] ?? null;
        $this->assertIsString($state);
        return $state;
    }

    private function parsedPage(string $content): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        return $document;
    }

    private function countCachedPages(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }

    /**
     * The `Content-Security-Policy` header as its directives, each as its list of sources.
     *
     * @return array<string, list<string>>
     */
    private function contentSecurityPolicy(ResponseInterface $response): array
    {
        $header = $response->getHeaderLine('Content-Security-Policy');
        $this->assertNotSame('', $header, 'The response carries no enforced Content Security Policy.');

        $directives = [];
        foreach (explode(';', $header) as $directive) {
            $parts = preg_split('/\s+/', trim($directive)) ?: [];
            $name = array_shift($parts);
            if ($name !== null && $name !== '') {
                $directives[$name] = array_values($parts);
            }
        }
        return $directives;
    }

    private function setUpStaticTemplateSite(): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @param array<string, string> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        // The page object only, and "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so a differently configured site needs another one.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-partners'],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
