<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Pages;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The map partial on a partner page: a site package renders it in its own page template
 * for the partner of the page.
 *
 * The shipped page template does not render a map. The fixture template of the site
 * package does, with the variables `mapSettings` and `mapAssets` the `partner-data`
 * processor adds. Both go through the processor because a PAGEVIEW page object ignores
 * `settings` of its own, so both shapes of a page object are tested.
 */
final class AcademicPartnerPageMapTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnerPageMap/pages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('pageObjects')]
    public function aPartnerWithCoordinatesIsShownOnAMapWithTheSettingsOfTheSite(string $sitePackage): void
    {
        $this->setUpTestCase($sitePackage);

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');
        $xpath = $this->parseRenderedPage($content);

        // Every setting, because the page object assigns them one by one, next to the
        // assignment of the plugin. The maximum zoom is the one the site changed.
        $map = $this->elementMatching($xpath, '//*[@id="map"]');
        $this->assertSame(
            [
                'data-academic-partners-center-lat' => '51.1657',
                'data-academic-partners-center-lng' => '10.4515',
                'data-academic-partners-zoom' => '6',
                'data-academic-partners-max-zoom' => '15',
                'data-academic-partners-padding' => '50',
                'data-academic-partners-tile-url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'data-academic-partners-attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
            ],
            [
                'data-academic-partners-center-lat' => $map->getAttribute('data-academic-partners-center-lat'),
                'data-academic-partners-center-lng' => $map->getAttribute('data-academic-partners-center-lng'),
                'data-academic-partners-zoom' => $map->getAttribute('data-academic-partners-zoom'),
                'data-academic-partners-max-zoom' => $map->getAttribute('data-academic-partners-max-zoom'),
                'data-academic-partners-padding' => $map->getAttribute('data-academic-partners-padding'),
                'data-academic-partners-tile-url' => $map->getAttribute('data-academic-partners-tile-url'),
                'data-academic-partners-attribution' => $map->getAttribute('data-academic-partners-attribution'),
            ],
        );
        $this->assertStringContainsString('frontend/map.js', $content);

        $partners = $xpath->query('//*[@id="map-partners"]/li');
        $this->assertInstanceOf(\DOMNodeList::class, $partners);
        $this->assertSame(1, $partners->length);
        $partner = $partners->item(0);
        $this->assertInstanceOf(\DOMElement::class, $partner);
        $this->assertSame('web-vision GmbH', $partner->getAttribute('data-name'));
        $this->assertSame('50.110924', $partner->getAttribute('data-lat'));
        $this->assertSame('8.682127', $partner->getAttribute('data-lng'));
        $this->assertSame('/web-vision', $partner->getAttribute('data-link'));
    }

    #[Test]
    #[DataProvider('pageObjects')]
    public function aPartnerWithoutCoordinatesGetsNoMap(string $sitePackage): void
    {
        $this->setUpTestCase($sitePackage);

        $content = $this->renderFrontendPage('https://www.acme.com/acme-ag');

        $this->assertStringContainsString('site-package-partner-page', $content);
        $this->assertStringNotContainsString('id="map"', $content);
        $this->assertStringNotContainsString('id="map-partners"', $content);
        $this->assertStringNotContainsString('frontend/map.js', $content);
    }

    /**
     * "Show on map" switched off: the partner has coordinates, and the partial still
     * renders no map for it.
     */
    #[Test]
    #[DataProvider('pageObjects')]
    public function aPartnerHiddenFromTheMapGetsNoMap(string $sitePackage): void
    {
        $this->setUpTestCase($sitePackage);

        $content = $this->renderFrontendPage('https://www.acme.com/switched-off-gmbh');

        $this->assertStringContainsString('site-package-partner-page', $content);
        $this->assertStringContainsString('Switched Off GmbH', $content);
        $this->assertStringNotContainsString('id="map"', $content);
        $this->assertStringNotContainsString('frontend/map.js', $content);
    }

    /**
     * The switch of the map script reaches a page template as `mapAssets`, and the partial
     * leaves out the module and the stylesheets of the map libraries with it. The map
     * element and the partners stay for a script of the site.
     */
    #[Test]
    #[DataProvider('pageObjects')]
    public function theSwitchedOffScriptIsLeftOutOnAPartnerPage(string $sitePackage): void
    {
        $this->setUpTestCase($sitePackage, ['EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript']);

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringNotContainsString('frontend/map.js', $content);
        $this->assertStringNotContainsString('leaflet.css', $content);
        $this->assertStringNotContainsString('MarkerCluster', $content);
        $this->assertStringContainsString('id="map"', $content);
        $this->assertStringContainsString('data-name="web-vision GmbH"', $content);
    }

    /**
     * A page template that renders the partial without the argument `assets` keeps the
     * script, even where the site switched it off: the partial cannot tell a template
     * that does not pass the switch from one that wants the script.
     */
    #[Test]
    #[DataProvider('pageObjects')]
    public function aPageTemplateWithoutTheAssetsArgumentKeepsTheScript(string $sitePackage): void
    {
        $this->setUpTestCase(
            $sitePackage,
            ['EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript'],
            'PartnerMapThemeWithoutAssets.typoscript',
        );

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('frontend/map.js', $content);
        $this->assertStringContainsString('leaflet.css', $content);
        $this->assertStringContainsString('id="map"', $content);
    }

    /**
     * The same switch as a site setting, on a site that names the aggregate set. The
     * processor reads it through the same constant, so the page leaves the script out.
     */
    #[Test]
    public function theSiteSettingLeavesTheScriptOutOnAPartnerPage(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                // The page object of the site package after the sets, then the template
                // of the site package that renders the map partial.
                'config' => '@import \'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackageAfterSets.typoscript\'' . LF
                    . '@import \'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/PartnerMapTheme.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'acme-site-set',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-partners'],
                    'settings' => ['plugin.tx_academicpartners.assets.js' => false],
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('site-package-partner-page', $content);
        $this->assertStringContainsString('id="map"', $content);
        $this->assertStringContainsString('data-name="web-vision GmbH"', $content);
        $this->assertStringNotContainsString('frontend/map.js', $content);
        $this->assertStringNotContainsString('leaflet.css', $content);
        $this->assertStringNotContainsString('MarkerCluster', $content);
    }

    public static function pageObjects(): \Generator
    {
        yield 'FLUIDTEMPLATE' => ['SitePackage.typoscript'];
        yield 'PAGEVIEW' => ['SitePackagePageView.typoscript'];
    }

    /**
     * @param list<string> $additionalConstants
     */
    private function setUpTestCase(string $sitePackage, array $additionalConstants = [], string $theme = 'PartnerMapTheme.typoscript'): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Constants/MapMaxZoom.typoscript',
                    ...$additionalConstants,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    // The site package first, the extension after it, as in an installation.
                    'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/' . $sitePackage,
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/' . $theme,
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }
}
