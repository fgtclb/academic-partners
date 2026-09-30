<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Pages;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * How a partner page is embedded in the page layout of the site, and which of its parts
 * an integrator replaces on their own.
 *
 * The page template renders the section "Main" of the layout named by the setting
 * "plugin.tx_academicpartners.page.layout", "Default" unless configured. Its paths sit
 * at the key 50 of the page object, so a site package registering its own at 100 - the
 * common key of a PAGEVIEW site package - keeps them on partner pages, and an override
 * between the two wins. A site package without a layout "Default" gets the fallback
 * layout of the extension, which renders the section alone, as the page rendered before
 * it had a layout at all.
 *
 * On a site configured through TypoScript records the site package is included before
 * the TypoScript of the extension and every integrator override after it, the order
 * they have in an installation. On a site set site the site package comes last - see
 * "setUpSiteSetSite()".
 */
final class AcademicPartnerPageLayoutTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const FIXTURES = 'EXT:academic_partners/Tests/Functional/Pages/Fixtures/';
    private const PARTNER_PAGE = 'https://www.acme.com/web-vision';
    private const CONTENT_ELEMENT = 'The partnership covers joint research.';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(__DIR__ . '/Fixtures/AcademicPartnerPageTemplateTest/Files/landscape.jpg', $folder . '/landscape.jpg');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnerPageLayoutTest/pages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * A site configured through a TypoScript record, the site package before the
     * extension and the integrator after it.
     *
     * @param string $sitePackage A file below "Fixtures/TypoScript/Setup/".
     * @param list<string> $integratorSetup Files below "Fixtures/TypoScript/Setup/".
     * @param list<string> $integratorConstants Files below "Fixtures/TypoScript/Constants/".
     */
    private function setUpSite(string $sitePackage, array $integratorSetup = [], array $integratorConstants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    ...array_map(static fn(string $file): string => self::FIXTURES . 'TypoScript/Constants/' . $file, $integratorConstants),
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    self::FIXTURES . 'TypoScript/Setup/' . $sitePackage,
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    ...array_map(static fn(string $file): string => self::FIXTURES . 'TypoScript/Setup/' . $file, $integratorSetup),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    /**
     * A site configured through the aggregate site set, with site settings. The site
     * package comes from a "sys_template" record, which is included after the sets - see
     * "SitePackageAfterSets.typoscript".
     *
     * @param array<string, string|int> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'' . self::FIXTURES . 'TypoScript/Setup/SitePackageAfterSetsWithLayouts.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
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

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageWithDefaultLayoutDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE, layouts at 10' => ['SitePackageWithLayouts.typoscript'];
        yield 'PAGEVIEW, paths at 10' => ['SitePackagePageViewAt10.typoscript'];
        yield 'PAGEVIEW, paths at 100' => ['SitePackagePageViewAt100.typoscript'];
    }

    /**
     * The PAGEVIEW site package renders its footer through a partial of its own, so
     * the case at the key 100 also proves that the extension no longer replaces the
     * site package's paths on partner pages.
     */
    #[Test]
    #[DataProvider('sitePackageWithDefaultLayoutDataProvider')]
    public function partnerPageRendersInsideTheDefaultLayoutOfTheSite(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertPartnerBetween($content, 'site-layout-default-header', 'site-layout-default-footer');
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageWithWideLayoutDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE' => ['SitePackageWithLayouts.typoscript'];
        yield 'PAGEVIEW, paths at 100' => ['SitePackagePageViewAt100.typoscript'];
    }

    /**
     * Every render after the first compile of the page template runs the compiled
     * class, which evaluates the layout name on its own - at the latest the second
     * partner page here.
     */
    #[Test]
    #[DataProvider('sitePackageWithWideLayoutDataProvider')]
    public function integratorNamesAnotherLayoutThroughTheConstant(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, integratorConstants: ['LayoutWide.typoscript']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);
        $this->assertPartnerBetween($content, 'site-layout-wide-header', 'site-layout-wide-footer');
        $this->assertStringNotContainsString('site-layout-default-header', $content);

        $compiled = $this->renderFrontendPage('https://www.acme.com/acme-ag');
        $header = strpos($compiled, 'site-layout-wide-header');
        $title = strpos($compiled, '<h1>Acme AG</h1>');
        $footer = strpos($compiled, 'site-layout-wide-footer');
        $this->assertIsInt($header, 'The layout is missing on the second partner page.');
        $this->assertIsInt($title, 'The partner content is missing on the second partner page.');
        $this->assertIsInt($footer, 'The layout is missing on the second partner page.');
        $this->assertLessThan($title, $header);
        $this->assertLessThan($footer, $title);
        $this->assertStringNotContainsString('site-layout-default-header', $compiled);
    }

    /**
     * An empty layout name is not a layout, and Fluid would fail on it: the page falls
     * back to "Default".
     */
    #[Test]
    public function emptyLayoutSettingFallsBackToTheDefaultLayout(): void
    {
        $this->setUpSite('SitePackageWithLayouts.typoscript', integratorConstants: ['LayoutEmpty.typoscript']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertPartnerBetween($content, 'site-layout-default-header', 'site-layout-default-footer');
    }

    #[Test]
    public function integratorNamesTheLayoutThroughTheSiteSetting(): void
    {
        $this->setUpSiteSetSite(['plugin.tx_academicpartners.page.layout' => 'Wide']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertPartnerBetween($content, 'site-layout-wide-header', 'site-layout-wide-footer');
    }

    #[Test]
    public function siteSetSiteRendersInsideTheDefaultLayoutWithoutSettings(): void
    {
        $this->setUpSiteSetSite([]);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertPartnerBetween($content, 'site-layout-default-header', 'site-layout-default-footer');
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageWithoutLayoutDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE' => ['SitePackage.typoscript'];
        yield 'PAGEVIEW' => ['SitePackagePageView.typoscript'];
    }

    /**
     * A site package that has no layout "Default" - the fixtures render their page
     * templates without one - gets the fallback layout of the extension: the partner
     * content alone, as before the template had a layout, and no exception.
     */
    #[Test]
    #[DataProvider('sitePackageWithoutLayoutDataProvider')]
    public function partnerPageRendersWithoutADefaultLayoutOfTheSite(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertStringContainsString('<h1>web-vision GmbH</h1>', $content);
        $this->assertStringContainsString(self::CONTENT_ELEMENT, $content);
        $this->assertStringNotContainsString('site-package-default-template', $content);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageDataProvider(): \Generator
    {
        yield 'FLUIDTEMPLATE' => ['SitePackageWithLayouts.typoscript'];
        yield 'PAGEVIEW, paths at 100' => ['SitePackagePageViewAt100.typoscript'];
    }

    /**
     * The override replaces the header and nothing else: media and content are still
     * rendered by the extension.
     */
    #[Test]
    #[DataProvider('sitePackageDataProvider')]
    public function integratorOverridesTheHeaderAlone(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, integratorSetup: ['HeaderOverrideAt75.typoscript']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertStringContainsString('<div class="project-partner-header">web-vision GmbH</div>', $content);
        $this->assertStringNotContainsString('<h1>web-vision GmbH</h1>', $content);
        $this->assertStringContainsString('<picture', $content);
        $this->assertStringContainsString(self::CONTENT_ELEMENT, $content);
    }

    /**
     * An override of the whole template, written before it had a layout, renders as it
     * is: without a layout Fluid renders the template itself.
     */
    #[Test]
    #[DataProvider('sitePackageDataProvider')]
    public function integratorOverridesTheWholePageTemplate(string $sitePackage): void
    {
        $this->setUpSite($sitePackage, integratorSetup: ['TemplateOverrideAt75.typoscript']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertStringContainsString('<div class="project-partner-page">web-vision GmbH</div>', $content);
        $this->assertStringNotContainsString('academic-partners-detail', $content);
    }

    /**
     * The subtitle is the core page field, which a FLUIDTEMPLATE page object hands over
     * as "data" and a PAGEVIEW one only through "page".
     */
    #[Test]
    #[DataProvider('sitePackageDataProvider')]
    public function headerShowsTheSubtitleOfThePage(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $xpath = $this->parseRenderedPage($this->renderFrontendPage(self::PARTNER_PAGE));
        $header = $this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-partners-detail__header ')]");
        $subtitle = $this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-partners-detail__header ')]/h1/following-sibling::*[contains(concat(' ', normalize-space(@class), ' '), ' academic-partners-detail__subtitle ')]");

        $this->assertCount(1, $header);
        $this->assertCount(1, $subtitle);
        $this->assertSame('Partner university since 2019', trim((string)$subtitle->item(0)?->textContent));
    }

    /**
     * The subtitle of the translation, not the one of the default language page: the
     * record on both page object types is the overlaid one.
     */
    #[Test]
    #[DataProvider('sitePackageDataProvider')]
    public function headerShowsTheSubtitleOfTheTranslatedPage(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/de/web-vision'));
        $subtitle = $this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-partners-detail__subtitle ')]");

        $this->assertCount(1, $subtitle);
        $this->assertSame('Partnerhochschule seit 2019', trim((string)$subtitle->item(0)?->textContent));
    }

    /**
     * PAGEVIEW reserves "page" but not "data", so a site package may assign a "data" of
     * its own. The template and the data processor read the page record from "page"
     * first: the heading, which comes from the processor, and the subtitle, which the
     * template reads, still show.
     */
    #[Test]
    public function headerReadsThePageRecordFromPageWhenTheSitePackageAssignsData(): void
    {
        $this->setUpSite('SitePackagePageViewAt100.typoscript', integratorSetup: ['SitePackageDataVariable.typoscript']);

        $content = $this->renderFrontendPage(self::PARTNER_PAGE);

        $this->assertStringContainsString('<h1>web-vision GmbH</h1>', $content);
        $this->assertStringContainsString('<p class="academic-partners-detail__subtitle">Partner university since 2019</p>', $content);
    }

    #[Test]
    #[DataProvider('sitePackageDataProvider')]
    public function headerRendersNoSubtitleElementForAPageWithoutOne(string $sitePackage): void
    {
        $this->setUpSite($sitePackage);

        $content = $this->renderFrontendPage('https://www.acme.com/acme-ag');

        $this->assertStringContainsString('<h1>Acme AG</h1>', $content);
        $this->assertStringNotContainsString('academic-partners-detail__subtitle', $content);
    }

    /**
     * The partner content sits between the two markers of the site layout.
     */
    private function assertPartnerBetween(string $content, string $headerMarker, string $footerMarker): void
    {
        $header = strpos($content, $headerMarker);
        $partner = strpos($content, 'academic-partners-detail');
        $element = strpos($content, self::CONTENT_ELEMENT);
        $footer = strpos($content, $footerMarker);
        $this->assertIsInt($header, sprintf('The layout marker "%s" is missing.', $headerMarker));
        $this->assertIsInt($partner, 'The partner content is missing.');
        $this->assertIsInt($element, 'The content element of the partner page is missing.');
        $this->assertIsInt($footer, sprintf('The layout marker "%s" is missing.', $footerMarker));
        $this->assertLessThan($partner, $header);
        $this->assertLessThan($element, $partner);
        $this->assertLessThan($footer, $element);
    }
}
