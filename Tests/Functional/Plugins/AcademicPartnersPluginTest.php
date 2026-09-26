<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders all four plugins of this extension in the frontend: `academicpartners_list`,
 * `academicpartners_map`, `academicpartners_partnershipslist` and
 * `academicpartners_partnershipsteaser`. They share one page tree, one site and one
 * TypoScript setup, which is why they share one test class.
 *
 * Partners are pages of doktype 40 mapped onto the `pages` table, so the fixtures are page
 * records carrying the partner columns. The two list-like plugins read their configuration
 * from the FlexForm of the content element, while the two partnership plugins take none and
 * resolve their records from the page the content element sits on
 * (`PartnershipRepository::findByPid()`).
 *
 * The header of a content element renders once: by default the content element layout
 * renders it and the plugins do not. A site whose layout renders no header switches
 * `renderContentElementHeader` on, and the templates then render the
 * `EXT:fluid_styled_content` `Header/All` partial themselves. The switched on cases guard
 * the `record` view variable as well: on TYPO3 v14 the partial renders the header through
 * it, and fails without it.
 */
final class AcademicPartnersPluginTest extends AbstractAcademicPartnersTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const HEADER = 'Our academic partners';
    private const SUBHEADER = 'Universities we work with';
    private const RENDER_HEADER_CONSTANTS = 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript';
    private const HEADER_PARTIAL_OVERRIDE_SETUP = 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/HeaderPartialOverride.typoscript';
    private const LAYOUT_WITHOUT_HEADER_SETUP = 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles
     * @param list<string> $additionalSetupFiles
     */
    private function setUpTestCase(
        string $dataSet,
        bool $withGermanLanguage = false,
        array $additionalConstantFiles = [],
        array $additionalSetupFiles = [],
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
                ],
            ],
        );
        $languages = [
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ];

        if ($withGermanLanguage) {
            // Falls back to English, so a content element that is not translated is
            // still rendered and the test can be about the partner record alone.
            $languages[] = $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: ['EN'],
            );
        }

        $this->writeFrontendPluginTestSite($languages);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * A partnership rendered inside a role group passes `grouped` down to `Partner/Header`,
     * which renders the partner title one level below the role heading — `h3` instead of the
     * `h2` an ungrouped item gets. Asserting the level is what proves the argument arrives.
     */
    private function assertGroupedPartnerHeading(string $content): void
    {
        $this->assertMatchesRegularExpression(
            '#<h3 class="card-title">\s*<a href="/alpha-university">Alpha University</a>\s*</h3>#',
            $content,
        );
    }

    #[Test]
    public function partnerListPluginRendersAllVisiblePartners(): void
    {
        $this->setUpTestCase('partnerListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partners-list', $content);
        $this->assertStringContainsString('academic-partners-itemlist', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        // Partners are collected across the whole site, not only below the current page.
        $this->assertStringContainsString('Delta Regional College', $content);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $content);
    }

    #[Test]
    public function partnerListPluginRendersTheFilterAndSortingForm(): void
    {
        $this->setUpTestCase('partnerListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partners-filtersorting', $content);
        $this->assertStringContainsString('Sorting field', $content);
        $this->assertStringContainsString('Sorting direction', $content);
        // The options are written by `CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`,
        // which `ViewHelpers\Form\SortingSelectViewHelper` no longer overrides - this is what
        // covers that here, the class has no test of its own.
        $this->assertStringContainsString('<option value="title" selected="selected">Title</option>', $content);
        $this->assertStringContainsString('<option value="lastUpdated">Last updated</option>', $content);
        $this->assertStringContainsString('<option value="asc" selected="selected">ascending</option>', $content);
    }

    #[Test]
    public function partnerListPluginHidesTheFilterAndSortingFormWhenConfigured(): void
    {
        $this->setUpTestCase('partnerListPage_hideFilterAndSorting');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('academic-partners-filtersorting', $content);
        // The list itself is unaffected by hiding the form.
        $this->assertStringContainsString('Alpha University', $content);
    }

    #[Test]
    public function partnerListPluginRendersHiddenPartnersWhenConfigured(): void
    {
        $this->setUpTestCase('partnerListPage_showHiddenRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Gamma Hidden Partner', $content);
    }

    #[Test]
    public function partnerListPluginRestrictsPartnersToTheSelectedPages(): void
    {
        $this->setUpTestCase('partnerListPage_pageRestriction');

        $content = $this->renderHomePage();
        // The `pages` field of the content element restricts by storage page, so only the
        // partner below the selected folder is left.
        $this->assertStringContainsString('Delta Regional College', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
    }

    #[Test]
    public function partnerListPluginRendersNoPartnersFoundLabelWithoutPartners(): void
    {
        $this->setUpTestCase('partnerListPage_noPartners');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partners-list', $content);
        $this->assertStringContainsString('No partners found.', $content);
    }

    #[Test]
    public function partnerMapPluginRendersPartnersAsMapMarkers(): void
    {
        $this->setUpTestCase('partnerMapPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partners-map', $content);
        $this->assertStringContainsString('id="map-partners"', $content);
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('data-lat="48.137154"', $content);
        $this->assertStringContainsString('data-lng="11.576124"', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $content);
    }

    /**
     * "Delta Regional College" has no coordinates. `Partner` types them as
     * non-nullable `float` defaulting to 0, so the record used to reach the template
     * as `data-lat="0" data-lng="0"` and was drawn as a marker in the Atlantic
     * (ACE-562). `data-lat="0"` is therefore what must be absent - never `data-lat=""`,
     * which the model cannot produce.
     */
    #[Test]
    public function partnerMapPluginOmitsPartnersWithoutCoordinates(): void
    {
        $this->setUpTestCase('partnerMapPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
        $this->assertStringNotContainsString('id="partner-13"', $content);
        $this->assertStringNotContainsString('data-lat="0"', $content);
    }

    /**
     * A place does not move when the page is translated, so the coordinates are
     * `allowLanguageSynchronization` and a translation carries the same pair as its
     * default record - which is the state this fixture is in, and the state
     * `Upgrades\SynchronizePartnerCoordinatesUpgradeWizard` puts existing sites into.
     *
     * The map has to work in a translated language like any other: the partner is drawn
     * at its coordinates, under its translated title. Before ACE-562 a translation that
     * had never been geocoded reached the template as `data-lat="0"` and was drawn in
     * the Atlantic in that language.
     */
    #[Test]
    public function partnerMapPluginDrawsATranslatedPartnerAtItsDefaultCoordinates(): void
    {
        $this->setUpTestCase('partnerMapPageTranslated', withGermanLanguage: true);

        $content = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE . 'de/home');

        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('data-lat="48.137154"', $content);
        $this->assertStringContainsString('data-lng="11.576124"', $content);
        $this->assertStringContainsString('Alpha Universitaet', $content);
        $this->assertStringNotContainsString('data-lat="0"', $content);
    }

    /**
     * With nothing left to draw the map is not rendered at all: an empty Leaflet canvas
     * centred on Germany says nothing, and an editor cannot tell it from a broken one.
     * The partner at 0/0 in this fixture is excluded for the same reason as the one
     * without any coordinates.
     */
    #[Test]
    public function partnerMapPluginRendersAMessageWhenNoPartnerHasCoordinates(): void
    {
        $this->setUpTestCase('partnerMapPage_noLocatedPartners');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partners-map', $content);
        $this->assertStringNotContainsString('id="map-partners"', $content);
        $this->assertStringNotContainsString('id="map"', $content);
        $this->assertStringContainsString('No partner with a location to show on the map.', $content);
    }

    #[Test]
    public function partnershipsListPluginRendersPartnershipsGroupedByRole(): void
    {
        $this->setUpTestCase('partnershipsListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partnerships-list', $content);
        $this->assertStringContainsString('academic-partnerships-list-item', $content);
        // With a role assigned the partnerships are grouped, and the role name becomes the
        // heading of each group.
        $this->assertStringContainsString('Research partner', $content);
        $this->assertStringContainsString('Funding partner', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        // Grouped items render one heading level below the role heading, which is only the
        // case when `grouped` reaches `Partner/Header`.
        $this->assertGroupedPartnerHeading($content);
    }

    #[Test]
    public function partnershipsListPluginRendersPartnershipsWithoutRole(): void
    {
        $this->setUpTestCase('partnershipsListPage_withoutRoles');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partnerships-list', $content);
        $this->assertStringContainsString('academic-partnerships-list-item', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Research partner', $content);
    }

    #[Test]
    public function partnershipsTeaserPluginRendersPartnershipsGroupedByRole(): void
    {
        $this->setUpTestCase('partnershipsTeaserPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partnerships-teaser', $content);
        $this->assertStringContainsString('academic-partnerships-teaser-item', $content);
        $this->assertStringContainsString('Research partner', $content);
        $this->assertStringContainsString('Funding partner', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertGroupedPartnerHeading($content);
    }

    #[Test]
    public function partnershipsTeaserPluginRendersPartnershipsWithoutRole(): void
    {
        $this->setUpTestCase('partnershipsTeaserPage_withoutRoles');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-partnerships-teaser', $content);
        $this->assertStringContainsString('academic-partnerships-teaser-item', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Research partner', $content);
    }

    private function setContentElementHeader(int $uid, int $headerLayout): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout],
                ['uid' => $uid],
            );
    }

    /**
     * Every view with the header layouts "Default", 2 and "Hidden", and the number of times
     * the header and the subheader have to render: "Default" is the layout the header
     * partial resolves through a setting, and the one a plugin rendering it without that
     * setting leaves an empty `<header>` for. A view names the data set, the page, the
     * content element and the class of the element the template wraps its output in.
     *
     * @return \Generator<string, array{string, string, int, string, int, int}>
     */
    public static function viewsAndHeaderLayouts(): \Generator
    {
        $views = [
            'partner list' => ['partnerListPage', 'https://www.acme.com/home', 1, 'academic-partners-list'],
            'partner map' => ['partnerMapPage', 'https://www.acme.com/home', 1, 'academic-partners-map'],
            'partnerships list' => ['partnershipsListPage', 'https://www.acme.com/home', 1, 'academic-partnerships-list'],
            'partnerships teaser' => ['partnershipsTeaserPage', 'https://www.acme.com/home', 1, 'academic-partnerships-teaser'],
        ];
        $headerLayouts = [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
        foreach ($views as $view => [$dataSet, $url, $contentElement, $wrapperClass]) {
            foreach ($headerLayouts as $name => [$headerLayout, $expectedHeadings]) {
                yield $view . ', ' . $name => [$dataSet, $url, $contentElement, $wrapperClass, $headerLayout, $expectedHeadings];
            }
        }
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginLeavesTheContentElementHeaderToTheLayout(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $wrapper = sprintf(
            '//*[@id = "c%d"]//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]',
            $contentElement,
            $wrapperClass,
        );
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER, $wrapper));
        $this->assertSame(0, $this->countHeadingsReading($content, self::SUBHEADER, $wrapper));
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginRendersTheContentElementHeaderWhenSwitchedOn(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet, false, [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP]);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $frame = sprintf('//*[@id = "c%d"]', $contentElement);
        // The fixture layout renders no header, so a heading inside the frame of the element
        // comes from the template. The first two assertions prove the fixture layout and the
        // template of the view rendered.
        $this->assertSame(1, $this->countContentElementHeaderNodes($content, $frame . '[contains(concat(" ", normalize-space(@class), " "), " frame-without-header ")]'));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            sprintf('%s//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $frame, $wrapperClass),
        ));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, $frame));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, $frame));
    }

    /**
     * A site package that renders the header its own way registers its own `Header/All`
     * above the paths of the extension, and that one renders instead of the partial of
     * EXT:fluid_styled_content, whose path sorts below every other one.
     */
    #[Test]
    public function aHeaderPartialOfTheSitePackageWinsOverTheShippedOne(): void
    {
        $this->setUpTestCase('partnerListPage', false, [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP, self::HEADER_PARTIAL_OVERRIDE_SETUP]);
        $this->setContentElementHeader(1, 2);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            '//*[@id = "c1"]//p[@class = "site-package-header"][normalize-space() = "' . self::HEADER . '"]',
        ));
    }
}
