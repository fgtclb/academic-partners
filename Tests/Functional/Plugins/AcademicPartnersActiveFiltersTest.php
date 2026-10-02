<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ActiveFiltersAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The active filter tags, the reset link and the result count of the partner list and map,
 * switched on by `filter.showActiveFilters`, `filter.showReset` and `filter.showResultCount`.
 *
 * Categories: Europe (1), Americas (2) and Asia (5) are regions, University (3) is a partner
 * type, and Europe and University are translated to German. Alpha University and Gamma
 * College are European universities, Beta Institute is in the Americas, and no partner is
 * in Asia.
 *
 * - `/home`: the list.
 * - `/map`: the map.
 * - `/europe`: a list with Europe preselected by the editor.
 * - `/filter-hidden`: a list whose filter is hidden.
 * - `/paginated`: a list with one partner per page.
 * - `/de/home`: the list in German.
 */
final class AcademicPartnersActiveFiltersTest extends AbstractAcademicPartnersTestCase
{
    use ActiveFiltersAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PREFIX = 'academic-partners';
    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const MAP_NAMESPACE = 'tx_academicpartners_map';
    private const ALL_ON = "plugin.tx_academicpartners.filter.showActiveFilters = 1\n"
        . "plugin.tx_academicpartners.filter.showReset = 1\n"
        . "plugin.tx_academicpartners.filter.showResultCount = 1\n";

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersActiveFilters/partnerListAndMapPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function everyActiveFilterIsATagThatRemovesOnlyThatFilter(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3', 'title', 'desc'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Europe', 'University'], array_keys($tags));
        $this->assertSame(
            ['filterCollection' => ['categories' => '3'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Europe']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame(
            ['filterCollection' => ['categories' => '1'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['University']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('Remove filter: Europe', $tags['Europe']['label']);
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertMatchesRegularExpression('#academic-partners-active-filters__reset[^>]*>\s*Reset all filters\s*</a>#', $content);
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * Without a category left the tag link still carries the sorting: a demand without any
     * argument would bring back what the editor preselected.
     */
    #[Test]
    public function removingTheLastFilterKeepsTheSorting(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '2', 'title', 'asc'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Americas'], array_keys($tags));
        $this->assertSame(
            ['sortingDirection' => 'asc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Americas']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('1 partner found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * On the bare page the list shows what the editor preselected. The tag removes the
     * preselection like any other filter. The reset link would lead to the page shown, so
     * it is offered only once the visitor selected something, removing the preselection
     * included, and then brings the preselection back.
     */
    #[Test]
    public function thePreselectionIsATagAndTheResetLinkBringsItBack(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage('https://www.acme.com/europe');

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Europe'], array_keys($tags));
        $this->assertNull($this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));

        $withoutPreselection = $this->renderFrontendPage('https://www.acme.com' . $tags['Europe']['href']);
        $this->assertSame([], $this->activeFilterTags($withoutPreselection, self::PREFIX));
        $this->assertSame('/europe', $this->activeFiltersResetLink($withoutPreselection, self::PREFIX));
        $this->assertSame('3 partners found', $this->activeFiltersResultCount($withoutPreselection, self::PREFIX));
    }

    /**
     * A visitor's selection on the list with a preselection: the reset link returns to the
     * preselected list.
     */
    #[Test]
    public function theResetLinkReturnsToThePreselection(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/europe', '2', 'title', 'asc'));

        $this->assertSame(['Americas'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $resetLink = $this->activeFiltersResetLink($content, self::PREFIX);
        $this->assertSame('/europe', $resetLink);

        $preselected = $this->renderFrontendPage('https://www.acme.com' . $resetLink);
        $this->assertSame(['Europe'], array_keys($this->activeFilterTags($preselected, self::PREFIX)));
    }

    /**
     * With nothing selected and nothing preselected there is nothing to reset: a visitor who
     * changed the sorting only, or went to the second page of the whole list, gets neither
     * tags nor a reset link.
     */
    #[Test]
    public function nothingIsResetWithoutAFilterOrAPreselection(): void
    {
        $this->setUpSite(self::ALL_ON);

        $sorted = $this->renderFrontendPage($this->listUrl('/home', '', 'title', 'desc'));
        $this->assertStringNotContainsString('academic-partners-active-filters', $sorted);
        $this->assertSame('3 partners found', $this->activeFiltersResultCount($sorted, self::PREFIX));

        $secondPage = $this->renderFrontendPage($this->listUrl('/paginated', '', 'title', 'asc') . '&tx_academicpartners_list%5Bdemand%5D%5BcurrentPage%5D=2');
        $this->assertSame(1, substr_count($secondPage, 'academic-partners-list__pagination'));
        $this->assertStringNotContainsString('academic-partners-active-filters', $secondPage);
    }

    /**
     * On the bare page without a preselection nothing is filtered and nothing is reset.
     */
    #[Test]
    public function theBarePageRendersNeitherTagsNorResetLink(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringNotContainsString('academic-partners-active-filters', $content);
        $this->assertSame('3 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The tags follow the type order of the category group, region before partner type,
     * whatever the uids: Asia (5) comes before University (3).
     */
    #[Test]
    public function theTagsFollowTheTypeOrder(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '3,5', 'title', 'asc'));

        $this->assertSame(['Asia', 'University'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertSame('0 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * A paginated list counts every partner it found, not those of the page shown.
     */
    #[Test]
    public function aPaginatedListCountsEveryPartner(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/paginated', '1', 'title', 'asc'));

        $this->assertSame(1, substr_count($content, 'academic-partners-list__pagination'));
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The settings are off by default: a site that sets none of them renders the list as
     * before, whatever the visitor filtered.
     */
    #[Test]
    public function nothingIsRenderedWithoutTheSettings(): void
    {
        $this->setUpSite();

        foreach (['/home', '/map'] as $path) {
            $content = $this->renderFrontendPage($this->listUrl($path, '1,3', 'title', 'asc', $path === '/map' ? self::MAP_NAMESPACE : self::LIST_NAMESPACE));

            $this->assertStringNotContainsString('academic-partners-active-filters', $content, $path);
            $this->assertStringNotContainsString('academic-partners-result-count', $content, $path);
        }
    }

    /**
     * Each setting switches its own part.
     */
    #[Test]
    public function eachSettingSwitchesItsOwnPart(): void
    {
        $this->setUpSite('plugin.tx_academicpartners.filter.showResultCount = 1');

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3', 'title', 'asc'));

        $this->assertSame([], $this->activeFilterTags($content, self::PREFIX));
        $this->assertNull($this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * Where the visitor cannot change the filter, the filter is not offered as tags either.
     * The count is no part of the filter.
     */
    #[Test]
    public function aHiddenFilterShowsNoTagsButTheCount(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/filter-hidden', '1,3', 'title', 'asc'));

        $this->assertStringNotContainsString('academic-partners-active-filters', $content);
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    #[Test]
    public function theTagsOfTheMapLeadBackToTheMap(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/map', '1,3', 'title', 'asc', self::MAP_NAMESPACE));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Europe', 'University'], array_keys($tags));
        parse_str((string)parse_url($tags['Europe']['href'], PHP_URL_QUERY), $query);
        $this->assertSame('/map', parse_url($tags['Europe']['href'], PHP_URL_PATH));
        $this->assertSame('map', $query[self::MAP_NAMESPACE]['action'] ?? null);
        $this->assertSame(['categories' => '3'], $query[self::MAP_NAMESPACE]['demand']['filterCollection'] ?? null);
        $this->assertSame('/map', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The tags carry the titles of the page's language.
     */
    #[Test]
    public function theTagsCarryTheTitlesOfThePageLanguage(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/de/home', '1,3', 'title', 'asc'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Europa', 'Universität'], array_keys($tags));
        $this->assertSame('Filter entfernen: Europa', $tags['Europa']['label']);
        $this->assertSame('/de/home', $this->activeFiltersResetLink($content, self::PREFIX));
    }

    /**
     * The three settings are site settings of the aggregate set as well.
     */
    #[Test]
    public function theSettingsAreSiteSettings(): void
    {
        $this->setUpSiteSetSite('active-filters', [
            'plugin.tx_academicpartners.filter.showActiveFilters' => true,
            'plugin.tx_academicpartners.filter.showReset' => true,
            'plugin.tx_academicpartners.filter.showResultCount' => true,
        ]);

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3', 'title', 'asc'));

        $this->assertSame(['Europe', 'University'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('2 partners found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The site as a static template configures it, with `$constants` added after the
     * constants of the extension.
     */
    private function setUpSite(string $constants = ''): void
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['constants' => $template['constants'] . LF . $constants], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite($this->languages());
    }

    /**
     * The same site configured through the aggregate site set and its site settings.
     *
     * @param non-empty-string $identifier
     * @param array<string, mixed> $settings
     */
    private function setUpSiteSetSite(string $identifier, array $settings): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => "@import 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript'",
            ],
        );
        $this->writeSiteConfiguration(
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-partners'],
                    'settings' => $settings,
                ],
            ),
            languages: $this->languages(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function languages(): array
    {
        return [
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            // Falls back to English, so the content element that is not translated is
            // rendered on the German page.
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
        ];
    }

    /**
     * A list URL as a filter submission redirects to. The demand is excluded from the cHash,
     * so it needs none.
     */
    private function listUrl(string $path, string $categories, string $sortingField, string $sortingDirection, string $namespace = self::LIST_NAMESPACE): string
    {
        $demand = ['sortingField' => $sortingField, 'sortingDirection' => $sortingDirection];
        if ($categories !== '') {
            $demand['filterCollection'] = ['categories' => $categories];
        }

        return 'https://www.acme.com' . $path . '?' . http_build_query([$namespace => ['demand' => $demand]]);
    }
}
