<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The map libraries a page with the partner map loads: Leaflet and its marker cluster
 * plugin as ES modules through the import map, built from their npm packages into
 * `Resources/Public/JavaScript/vendor/` (see `Build/vendor.mjs`), and their stylesheets.
 *
 * What the map module asks of the libraries is tested in `Tests/JavaScript/map.test.ts`.
 */
final class AcademicPartnersMapLibrariesTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    private const VENDOR = 'typo3conf/ext/academic_partners/Resources/Public/JavaScript/vendor/';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPlugin/partnerMapPage.csv');
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
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theImportMapOfAMapPagePublishesBothLibraries(): void
    {
        $imports = $this->importMap($this->renderFrontendPage('https://www.acme.com/home'));

        $this->assertArrayHasKey('leaflet', $imports);
        $this->assertArrayHasKey('leaflet.markercluster', $imports);
        $this->assertStringContainsString(self::VENDOR . 'leaflet/1.9.4/leaflet.js', $imports['leaflet']);
        $this->assertStringContainsString(
            self::VENDOR . 'leaflet.markercluster/1.5.3/leaflet.markercluster.js',
            $imports['leaflet.markercluster'],
        );
    }

    #[Test]
    public function aMapPageLoadsTheStylesheetsOfBothLibrariesAndNoClassicScript(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString(self::VENDOR . 'leaflet/1.9.4/leaflet.css', $content);
        $this->assertStringContainsString(self::VENDOR . 'leaflet.markercluster/1.5.3/MarkerCluster.css', $content);
        $this->assertStringContainsString(self::VENDOR . 'leaflet.markercluster/1.5.3/MarkerCluster.Default.css', $content);
        $this->assertStringContainsString('frontend/map.js', $content);

        // The deprecated classic files stay in the extension, but the map loads none of them.
        $this->assertStringNotContainsString('Public/JavaScript/leaflet.js', $content);
        $this->assertStringNotContainsString('Public/JavaScript/markerCluster.js', $content);
        $this->assertStringNotContainsString('Public/Css/leaflet.css', $content);
        $this->assertStringNotContainsString('Public/Css/markerCluster.css', $content);
    }

    /**
     * The map module takes the directory of its marker images from this attribute, so the
     * map shows the marker icon of this extension, as the classic stylesheet made it do,
     * instead of the one of Leaflet.
     */
    #[Test]
    public function aMapPageNamesTheMarkerIconOfTheExtension(): void
    {
        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));

        $icon = $this->elementMatching($xpath, '//*[@id="map"]')->getAttribute('data-academic-partners-marker-icon');
        $this->assertMatchesRegularExpression('#typo3conf/ext/academic_partners/Resources/Public/Images/Map/marker-icon\.png(\?\d+)?$#', $icon);
        foreach (['marker-icon.png', 'marker-icon-2x.png', 'marker-shadow.png'] as $image) {
            $this->assertFileExists(GeneralUtility::getFileAbsFileName('EXT:academic_partners/Resources/Public/Images/Map/' . $image));
        }
    }

    /**
     * The import map names a version the build has to have written. A version change in
     * `Build/package.json` moves the directory, and this is what notices a path that
     * stayed behind.
     */
    #[Test]
    public function everyLibraryTheImportMapNamesExists(): void
    {
        /** @var array{imports: array<string, string>} $configuration */
        $configuration = require __DIR__ . '/../../../Configuration/JavaScriptModules.php';

        foreach (['leaflet', 'leaflet.markercluster'] as $specifier) {
            $path = GeneralUtility::getFileAbsFileName($configuration['imports'][$specifier]);
            $this->assertFileExists($path, $specifier);
        }
    }

    /**
     * The partial names the version in the paths of the stylesheets as well, and they are
     * not in the import map.
     */
    #[Test]
    public function everyVendorStylesheetAMapPageLinksExists(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        preg_match_all('#href="[^"]*/Resources/Public/JavaScript/vendor/([^"?]+\.css)#', $content, $matches);
        $this->assertCount(3, $matches[1]);
        foreach ($matches[1] as $stylesheet) {
            $this->assertFileExists(
                GeneralUtility::getFileAbsFileName('EXT:academic_partners/Resources/Public/JavaScript/vendor/' . $stylesheet),
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function importMap(string $content): array
    {
        $found = preg_match('#<script type="importmap"[^>]*>(.*?)</script>#s', $content, $matches);
        $this->assertSame(1, $found, 'The page has no import map.');
        /** @var array{imports?: array<string, string>} $importMap */
        $importMap = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

        return $importMap['imports'] ?? [];
    }
}
