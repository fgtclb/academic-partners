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
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationChangedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

/**
 * The sorting and filter form of the partner list and map under the Content Security
 * Policy TYPO3 sends once a site enforces it (ACE-924).
 *
 * The selects submit the form with an inline `onchange` handler, which a nonce does not
 * cover. The extension allows the hash of its text in `script-src-attr`, so the policy of
 * every site that inherits the default one lets the handler run. For a visitor without
 * JavaScript the form renders a submit button inside `<noscript>`.
 *
 * The list is on `/home`, the map on `/map`, see `AcademicPartnersListFilterTest` for the
 * categories.
 */
final class AcademicPartnersContentSecurityPolicyTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const FORM = '//form[@name="demand"]';

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
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        // The scopes of the policy are singletons that keep the site object of the first
        // request of the process, which may be one of another test with another policy.
        Scope::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersListFilter/partnerListAndMapPages.csv');
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

    protected function tearDown(): void
    {
        Scope::reset();
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginPageDataProvider(): \Generator
    {
        yield 'list' => ['https://www.acme.com/home'];
        yield 'map' => ['https://www.acme.com/map'];
    }

    /**
     * Every inline handler of the page is allowed by `script-src-attr`, together with
     * `'unsafe-hashes'`, without which a browser applies no hash to an attribute. Neither
     * reaches `script-src`, which keeps what TYPO3 sends for script elements.
     */
    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function theEnforcedPolicyAllowsEveryInlineHandlerOfTheForm(string $url): void
    {
        $response = $this->requestFrontendPage($url);
        $this->assertSame(200, $response->getStatusCode());

        $handlers = $this->inlineHandlers((string)$response->getBody());
        $this->assertSame(['this.form.submit()'], array_values(array_unique($handlers)));
        $this->assertCount(5, $handlers, 'The sorting field, the sorting direction and three category filters.');

        $policy = $this->contentSecurityPolicy($response);
        $this->assertArrayHasKey('script-src-attr', $policy);
        $this->assertArrayHasKey('script-src', $policy);
        $this->assertContains("'unsafe-hashes'", $policy['script-src-attr']);
        $this->assertContains("'sha256-" . base64_encode(hash('sha256', 'this.form.submit()', true)) . "'", $policy['script-src-attr']);
        $this->assertNotContains("'unsafe-hashes'", $policy['script-src']);
        $this->assertNotContains("'unsafe-inline'", $policy['script-src']);
        $this->assertSame([], preg_grep('/^\'sha256-/', $policy['script-src']));
    }

    /**
     * A site that allows inline scripts in `script-src` keeps that for event handler
     * attributes: the directive of the extension is taken out again, so the browser falls
     * back to `script-src`, as it did before the extension declared one.
     */
    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function aSiteThatAllowsInlineScriptsGetsNoScriptSrcAttr(string $url): void
    {
        $this->writeSiteContentSecurityPolicy(<<<'YAML'
            mutations:
              - mode: extend
                directive: 'script-src'
                sources:
                  - "'unsafe-inline'"
            YAML);

        $policy = $this->contentSecurityPolicy($this->requestFrontendPage($url));

        $this->assertArrayNotHasKey('script-src-attr', $policy);
        $this->assertArrayHasKey('script-src', $policy);
        $this->assertContains("'unsafe-inline'", $policy['script-src']);
    }

    /**
     * A `script-src-attr` the site declares itself stays, with the hash of the extension
     * in it, even where `script-src` allows inline scripts.
     */
    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function aScriptSrcAttrOfTheSiteIsLeftAsItIs(string $url): void
    {
        $siteHash = "'sha256-" . base64_encode(hash('sha256', 'this.form.requestSubmit()', true)) . "'";
        $this->writeSiteContentSecurityPolicy(<<<YAML
            mutations:
              - mode: extend
                directive: 'script-src'
                sources:
                  - "'unsafe-inline'"
              - mode: extend
                directive: 'script-src-attr'
                sources:
                  - "'unsafe-hashes'"
                  - "{$siteHash}"
            YAML);

        $policy = $this->contentSecurityPolicy($this->requestFrontendPage($url));

        $this->assertArrayHasKey('script-src-attr', $policy);
        $this->assertContains("'unsafe-hashes'", $policy['script-src-attr']);
        $this->assertContains($siteHash, $policy['script-src-attr']);
        $this->assertContains("'sha256-" . base64_encode(hash('sha256', 'this.form.submit()', true)) . "'", $policy['script-src-attr']);
    }

    /**
     * A `script-src-attr` the site extends by `'unsafe-inline'` loses the hash of the
     * extension, next to which a browser would ignore the `'unsafe-inline'` of the site.
     */
    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function aScriptSrcAttrOfTheSiteWithUnsafeInlineLosesTheHash(string $url): void
    {
        $this->writeSiteContentSecurityPolicy(<<<'YAML'
            mutations:
              - mode: extend
                directive: 'script-src-attr'
                sources:
                  - "'unsafe-inline'"
            YAML);

        $policy = $this->contentSecurityPolicy($this->requestFrontendPage($url));

        $this->assertArrayHasKey('script-src-attr', $policy);
        $this->assertContains("'unsafe-inline'", $policy['script-src-attr']);
        $this->assertSame([], preg_grep('/^\'sha256-/', $policy['script-src-attr']));
    }

    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function theFormRendersASubmitButtonForVisitorsWithoutJavaScript(string $url): void
    {
        $xpath = $this->xpath($this->renderFrontendPage($url));

        $buttons = $xpath->query(self::FORM . '//noscript//button[@type="submit"]');
        $this->assertNotFalse($buttons);
        $this->assertCount(1, $buttons);
        $this->assertSame('Show partners', trim((string)$buttons->item(0)?->textContent));
        $this->assertCount(1, $xpath->query(self::FORM . '//button') ?: [], 'The button is the only one of the form, and only inside <noscript>.');
    }

    /**
     * The button submits what the selects hold, as their handler does: a submission
     * without a field of its own, answered with the redirect to the filtered list.
     */
    #[Test]
    public function aSubmissionOfTheFormRedirectsToTheFilteredList(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2']]],
        ]);

        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
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

    /**
     * The text of every inline event handler attribute of the page.
     *
     * @return list<string>
     */
    private function inlineHandlers(string $content): array
    {
        $handlers = [];
        foreach ($this->xpath($content)->query('//@*[starts-with(name(), "on")]') ?: [] as $attribute) {
            $handlers[] = (string)$attribute->nodeValue;
        }
        return $handlers;
    }

    private function xpath(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NOERROR | LIBXML_NOWARNING);
        return new \DOMXPath($document);
    }
}
