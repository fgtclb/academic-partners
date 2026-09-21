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
 * Renders the image of the three list-like plugins of this extension through the shared
 * image partial of academic_base.
 *
 * All three show the same thing: the first `media` file of the partner page, which is the
 * partner logo. They therefore all ask for the `logo` preset, which applies no crop and
 * passes a vector file through - `card` would cut a wide logo into a card ratio and would
 * declare four sources instead of two, which is what the source count asserts.
 *
 * The fixture gives Alpha University an 800 x 600 raster logo and Beta Institute the SVG
 * one, so every request renders both branches of the partial at once.
 */
final class AcademicPartnersImageRenderingTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/AcademicPartnersImage/';

    /**
     * The `logo` preset renders two sources, and its widest is 320 pixels - below the 800
     * of the fixture file, so the fallback is processed down to exactly that.
     */
    private const LOGO_SOURCES = 2;
    private const LOGO_FALLBACK_WIDTH = 320;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'Files/landscape.jpg', $folder . '/landscape.jpg');
        copy(self::FIXTURES . 'Files/logo.svg', $folder . '/logo.svg');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'partnerImagePages.csv');
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
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    /**
     * @return array{0: \DOMElement, 1: \DOMElement} the item of Alpha University and the one of
     *         Beta Institute, in the order the plugin renders them
     */
    private function itemsOf(\DOMXPath $xpath, string $itemClass): array
    {
        $items = [];
        foreach ($this->nodesMatching($xpath, sprintf("//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", $itemClass)) as $item) {
            $this->assertInstanceOf(\DOMElement::class, $item);
            $items[] = $item;
        }
        $this->assertCount(2, $items);

        return [$items[0], $items[1]];
    }

    #[Test]
    public function partnerListItemShowsTheLogoAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [$withRasterLogo] = $this->itemsOf($xpath, 'academic-partners-item');
        $this->assertRendersResponsivePicture(
            $xpath,
            $withRasterLogo,
            self::LOGO_SOURCES,
            self::LOGO_FALLBACK_WIDTH,
            'card-img-top img-fluid',
            // The alternative text of the file reference, which the partial passes no
            // argument for and the image view helper therefore keeps.
            'The logo of Alpha University',
        );
    }

    #[Test]
    public function partnerListItemPassesAVectorLogoThrough(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [, $withVectorLogo] = $this->itemsOf($xpath, 'academic-partners-item');
        $this->assertRendersUnprocessedSvg(
            $xpath,
            $withVectorLogo,
            '/logo.svg',
            'card-img-top img-fluid',
            'The logo of Beta Institute',
        );
    }

    #[Test]
    public function partnershipsListItemShowsTheLogoAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/partnerships'));
        [$withRasterLogo, $withVectorLogo] = $this->itemsOf($xpath, 'academic-partnerships-list-item');
        $this->assertRendersResponsivePicture(
            $xpath,
            $withRasterLogo,
            self::LOGO_SOURCES,
            self::LOGO_FALLBACK_WIDTH,
            'card-img-top img-fluid',
        );
        $this->assertRendersUnprocessedSvg(
            $xpath,
            $withVectorLogo,
            '/logo.svg',
            'card-img-top img-fluid',
            'The logo of Beta Institute',
        );
    }

    #[Test]
    public function partnershipsTeaserItemShowsTheLogoAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/teaser'));
        [$withRasterLogo, $withVectorLogo] = $this->itemsOf($xpath, 'academic-partnerships-teaser-item');
        $this->assertRendersResponsivePicture(
            $xpath,
            $withRasterLogo,
            self::LOGO_SOURCES,
            self::LOGO_FALLBACK_WIDTH,
            'card-img-top img-fluid',
        );
        $this->assertRendersUnprocessedSvg(
            $xpath,
            $withVectorLogo,
            '/logo.svg',
            'card-img-top img-fluid',
            'The logo of Beta Institute',
        );
    }

    /**
     * The academic_base partials are registered below the key of the project constant, so a
     * project overrides `Academic/Image.html` the way it overrides any partial of this
     * extension.
     */
    #[Test]
    public function projectOverrideOfTheSharedImagePartialWins(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'partnerImagePages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ImagePartialOverride.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(2, substr_count($content, '<span class="project-image-override">logo</span>'));
        $this->assertStringNotContainsString('<picture', $content);
    }
}
