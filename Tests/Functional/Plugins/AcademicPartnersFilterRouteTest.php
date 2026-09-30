<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * The partner list behind a route enhancer that maps its category filter with the
 * `CategoryFilterMapper` aspect of `EXT:category_types`: `/home/filter/americas-2` instead
 * of a query argument.
 *
 * The list excludes its demand from the cache hash, so the readable URL needs no cHash and
 * every filter URL of the page shares one page cache entry, exactly as the query argument
 * URLs do.
 *
 * Categories: Americas (2) and Europe (6) are regions, University (3) is a partner type,
 * Physics (7) is a category of no partner category type. Alpha University is a European
 * university, Beta Institute is in the Americas, Delta Regional College in Europe. `/home`
 * has a German translation, `/de/home`, and so have Americas ("Amerika") and Beta Institute
 * ("Beta Institut").
 */
final class AcademicPartnersFilterRouteTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const FORM_CLASS = 'academic-partners-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            // What every new installation gets: a URL with arguments the cache hash covers
            // and no cHash is a 404, not an uncached page.
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend, and
                        // the page cache entries are what this test counts.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersFilterUrl/partnerListPages.csv');
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
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'routeEnhancers' => [
                        'PartnerList' => [
                            'type' => 'Extbase',
                            'extension' => 'AcademicPartners',
                            'plugin' => 'List',
                            'limitToPages' => [2],
                            'routes' => [
                                [
                                    'routePath' => '/filter/{categories}',
                                    '_controller' => 'Partner::list',
                                    '_arguments' => [
                                        'categories' => 'demand/filterCollection/categories',
                                    ],
                                ],
                            ],
                            'defaultController' => 'Partner::list',
                            'requirements' => [
                                'categories' => '[^/]+',
                            ],
                            'aspects' => [
                                'categories' => [
                                    'type' => 'CategoryFilterMapper',
                                    'group' => 'partners',
                                ],
                            ],
                        ],
                    ],
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                // Falls back to English, so the content element, the region Europe and the
                // partners without a translation render on the German page as well.
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
            ],
        );
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function aFilterGeneratesAReadablePathWithoutCacheHash(): void
    {
        $this->assertSame('https://www.acme.com/home/filter/americas-2', $this->generateListUri(0, '2'));
        $this->assertSame('https://www.acme.com/home/filter/americas-2,europe-6', $this->generateListUri(0, '2,6'));
        $this->assertSame('https://www.acme.com/de/home/filter/amerika-2', $this->generateListUri(1, '2'));
    }

    #[Test]
    public function aReadablePathRendersTheFilteredList(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home/filter/americas-2');

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
        $this->assertMatchesRegularExpression('#<option value="2"[^>]* selected="selected">Americas</option>#', $content);
    }

    #[Test]
    public function aReadablePathRendersTheFilteredListInItsLanguage(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/de/home/filter/amerika-2');

        $this->assertStringContainsString('Beta Institut<', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertMatchesRegularExpression('#<option value="2"[^>]* selected="selected">Amerika</option>#', $content);
    }

    /**
     * The path is read by uid, so a link that still carries an old title, or the title of
     * another language, shows the same list.
     */
    #[Test]
    public function aPathWithAnotherTitleRendersTheSameList(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home/filter/south-america-2');

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
    }

    #[Test]
    public function aCategoryOfAnotherGroupIsNotFound(): void
    {
        $response = $this->requestFrontendPage('https://www.acme.com/home/filter/physics-7');

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * The filter form still posts, and the list answers with a redirect built by the URI
     * builder - which now takes the route. The sorting has no route of its own here and
     * stays a query argument, which as part of the demand needs no cHash either.
     */
    #[Test]
    public function submittingTheFilterFormRedirectsToTheReadablePath(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2']]],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertSame('/home/filter/americas-2', parse_url($location, PHP_URL_PATH));
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(
            [self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'asc', 'sortingField' => 'title']]],
            $query,
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
    }

    /**
     * The filter is a dynamic route argument. A static one would be part of the page cache
     * identifier and give every combination of categories a page cache entry of its own,
     * which the list avoids by keeping its demand out of the cache hash.
     */
    #[Test]
    public function filterPathsShareOneCachedPage(): void
    {
        $this->getConnectionPool()->getConnectionForTable('cache_pages')->truncate('cache_pages');
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');

        $content = $this->renderFrontendPage('https://www.acme.com/home/filter/americas-2');
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertSame(1, $this->countCachedPages());

        $content = $this->renderFrontendPage('https://www.acme.com/home/filter/europe-6');
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertSame(1, $this->countCachedPages());
    }

    private function generateListUri(int $languageId, string $categories): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');

        return (string)$site->getRouter()->generateUri(2, [
            '_language' => $site->getLanguageById($languageId),
            self::LIST_NAMESPACE => [
                'action' => 'list',
                'controller' => 'Partner',
                'demand' => ['filterCollection' => ['categories' => $categories]],
            ],
        ]);
    }

    private function countCachedPages(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }
}
