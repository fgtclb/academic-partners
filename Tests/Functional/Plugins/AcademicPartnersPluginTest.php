<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
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
 */
final class AcademicPartnersPluginTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
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

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPlugin/' . $dataSet . '.csv');
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

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The header of the content element is not part of any template of this extension — it
     * comes from `lib.contentElement`, which is what `PLUGIN_TYPE_CONTENT_ELEMENT` wires up.
     * On TYPO3 v14 that header partial renders through the `record` view variable, so this is
     * the assertion that fails should the plugin ever be registered without it.
     */
    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
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
    public function partnerListPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('partnerListPage');
        $this->setContentElementHeader('Our academic partners');

        $this->assertStringContainsString('Our academic partners', $this->renderHomePage());
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

    #[Test]
    public function partnerMapPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('partnerMapPage');
        $this->setContentElementHeader('Where our partners are');

        $this->assertStringContainsString('Where our partners are', $this->renderHomePage());
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
    public function partnershipsListPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('partnershipsListPage');
        $this->setContentElementHeader('Our partnerships');

        $this->assertStringContainsString('Our partnerships', $this->renderHomePage());
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

    #[Test]
    public function partnershipsTeaserPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('partnershipsTeaserPage');
        $this->setContentElementHeader('Selected partnerships');

        $this->assertStringContainsString('Selected partnerships', $this->renderHomePage());
    }
}
