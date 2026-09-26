<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The category filters of the partner list and map, as the settings `filter.categoryTypes`,
 * `filter.visibleCount` and `filter.hideDisabledOptions` shape them, and the "All" option
 * label of each filter.
 *
 * Categories: Europe (1) and Americas (2) are regions, University (3) is a partner type,
 * Quality Education (4) and Climate Action (5) are SDGs. Alpha University is a European
 * university working on Quality Education, Beta Institute is in the Americas. No partner
 * carries Climate Action, and no category of the type `collaboration_type` exists. The type
 * order of the group is region, partner type, collaboration type, SDG.
 *
 * The list is on `/home`, the map on `/map`.
 */
final class AcademicPartnersListFilterTest extends AbstractAcademicPartnersTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const FORM_CLASS = 'academic-partners-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersListFilter/partnerListAndMapPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The site as a static template configures it. `$constants` and `$setup` are added after
     * the TypoScript of the extension, the way a site package adds its own.
     */
    private function setUpSite(string $constants = '', string $setup = ''): void
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
        $this->appendToSiteTemplate($constants, $setup);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * The same site configured through the aggregate site set and its site settings. The
     * page object comes from a `sys_template` record, which is included after the sets.
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
            // The site identifier is part of several caches the test instance keeps for the
            // whole class - the site settings among them - so every site of a set needs an
            // identifier of its own.
            identifier: $identifier,
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

    private function appendToSiteTemplate(string $constants, string $setup): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . LF . $constants, 'config' => $template['config'] . LF . $setup],
            ['uid' => $template['uid']],
        );
    }

    private function filterSettings(string $categoryTypes = '', int $visibleCount = 0, bool $hideDisabledOptions = false): string
    {
        return 'plugin.tx_academicpartners.filter.categoryTypes = ' . $categoryTypes . LF
            . 'plugin.tx_academicpartners.filter.visibleCount = ' . $visibleCount . LF
            . 'plugin.tx_academicpartners.filter.hideDisabledOptions = ' . (int)$hideDisabledOptions . LF;
    }

    /**
     * The list on `/home` after a visitor filtered it: the POST the form sends, answered with
     * a redirect, and the page that redirect leads to.
     *
     * @param array<string, string> $filterCollection
     */
    private function renderFilteredList(array $filterCollection): string
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/home',
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => $filterCollection]]],
        ));

        return $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
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
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function pluginPageAndNamespaceDataProvider(): \Generator
    {
        yield 'list' => ['https://www.acme.com/home', self::LIST_NAMESPACE];
        yield 'map' => ['https://www.acme.com/map', 'tx_academicpartners_map'];
    }

    /**
     * The markup of the filters as it was before the settings existed, pinned for the list
     * and the map: every type with a category in the type order of the group, one cell each,
     * the generic "All" label, and the SDG without a partner as a disabled option.
     */
    #[DataProvider('pluginPageAndNamespaceDataProvider')]
    #[Test]
    public function withoutSettingsTheFiltersRenderAsBefore(string $url, string $pluginNamespace): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage($url);

        $this->assertSame(
            ['visible' => ['region', 'partner_type', 'sdg'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame($this->defaultFilterCells($pluginNamespace), $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function theConfiguredFilterTypesAreOfferedInTheirOrder(string $url): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'sdg,region'));

        $content = $this->renderFrontendPage($url);

        $this->assertSame(['sdg', 'region'], $this->renderedCategoryFilters($content, self::FORM_CLASS)['visible']);
    }

    /**
     * `collaboration_type` has no category and is left out before the count applies, so
     * the count is one of the filters that render.
     */
    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function theFiltersAfterTheVisibleCountAreBehindMoreFilters(string $url): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'collaboration_type,sdg,region,partner_type', visibleCount: 1));

        $content = $this->renderFrontendPage($url);

        $this->assertSame(
            ['visible' => ['sdg'], 'more' => ['region', 'partner_type'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    #[Test]
    public function aVisibleCountCoveringEveryFilterRendersNoDisclosure(): void
    {
        $this->setUpSite(constants: $this->filterSettings(visibleCount: 3));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['region', 'partner_type', 'sdg'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, string>, 1: string}>
     */
    public static function activeFilterDataProvider(): \Generator
    {
        yield 'a filter behind the disclosure' => [['region' => '1'], 'open'];
        yield 'a filter shown right away' => [['sdg' => '4'], 'closed'];
        yield 'a filter behind the disclosure, cleared' => [['region' => ''], 'closed'];
    }

    /**
     * @param array<string, string> $filterCollection
     */
    #[DataProvider('activeFilterDataProvider')]
    #[Test]
    public function moreFiltersIsOpenWhileOneOfItsFiltersIsActive(array $filterCollection, string $disclosure): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'sdg,region,partner_type', visibleCount: 1));

        $content = $this->renderFilteredList($filterCollection);

        $this->assertSame($disclosure, $this->renderedCategoryFilters($content, self::FORM_CLASS)['disclosure']);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function labelOverrideDataProvider(): \Generator
    {
        yield 'list, extension' => ['https://www.acme.com/home', 'plugin.tx_academicpartners'];
        yield 'list, plugin' => ['https://www.acme.com/home', 'plugin.tx_academicpartners_list'];
        yield 'map, extension' => ['https://www.acme.com/map', 'plugin.tx_academicpartners'];
        yield 'map, plugin' => ['https://www.acme.com/map', 'plugin.tx_academicpartners_map'];
    }

    /**
     * A label of its own for one type, set through `_LOCAL_LANG` of the extension or of the
     * plugin, and none for another: the one reads its own, the other the label every filter
     * read before. Up to TYPO3 v13 the extension path is only read because the partial
     * passes the extension name in UpperCamelCase.
     */
    #[DataProvider('labelOverrideDataProvider')]
    #[Test]
    public function theAllOptionReadsALabelOfItsTypeWhenOneExists(string $url, string $typoScriptPath): void
    {
        $this->setUpSite(setup: $typoScriptPath . '._LOCAL_LANG.default.sys_category.partners.allOptions.region = All regions');

        $content = $this->renderFrontendPage($url);

        $this->assertSame(['All regions', 'Europe', 'Americas'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'region'));
        $this->assertSame(['All options', 'Quality Education', 'Climate Action (disabled)'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'sdg'));
    }

    #[DataProvider('pluginPageDataProvider')]
    #[Test]
    public function optionsWithoutPartnersAreLeftOutOnDemand(string $url): void
    {
        $this->setUpSite(constants: $this->filterSettings(hideDisabledOptions: true));

        $content = $this->renderFrontendPage($url);

        $this->assertSame(['All options', 'Quality Education'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'sdg'));
    }

    #[Test]
    public function theSiteSettingsConfigureTheFilters(): void
    {
        $this->setUpSiteSetSite('acme-site-settings', [
            'plugin.tx_academicpartners.filter.categoryTypes' => 'sdg,region',
            'plugin.tx_academicpartners.filter.visibleCount' => 1,
            'plugin.tx_academicpartners.filter.hideDisabledOptions' => true,
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['sdg'], 'more' => ['region'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(['All options', 'Quality Education'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'sdg'));
    }

    /**
     * The site set without any site setting renders what the static template renders
     * without a constant: the declared defaults are the defaults of the constants - no
     * filter left out, none behind "More filters", no option hidden.
     */
    #[Test]
    public function theSiteSetDefaultsRenderTheFiltersAsBefore(): void
    {
        $this->setUpSiteSetSite('acme-site-set-defaults', []);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['region', 'partner_type', 'sdg'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame($this->defaultFilterCells(self::LIST_NAMESPACE), $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    /**
     * The cells of the filters before the settings existed, with the plugin namespace of the
     * list or the map in place of `%1$s`.
     */
    private const DEFAULT_FILTER_CELLS = [
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="region" class="form-label"> Region </label><select onchange="this.form.submit()" id="region" class="form-select" name="%1$s[demand][filterCollection][region]"><option value="">All options</option><option value="1" class="level-0">Europe</option><option value="2" class="level-0">Americas</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="partner_type" class="form-label"> Partner Type </label><select onchange="this.form.submit()" id="partner_type" class="form-select" name="%1$s[demand][filterCollection][partner_type]"><option value="">All options</option><option value="3" class="level-0">University</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="sdg" class="form-label"> SDG </label><select onchange="this.form.submit()" id="sdg" class="form-select" name="%1$s[demand][filterCollection][sdg]"><option value="">All options</option><option value="4" class="level-0">Quality Education</option><option value="5" class="level-0" disabled>Climate Action</option></select></div>',
    ];

    /**
     * @return list<string>
     */
    private function defaultFilterCells(string $pluginNamespace): array
    {
        return array_map(
            static fn(string $cell): string => sprintf($cell, $pluginNamespace),
            self::DEFAULT_FILTER_CELLS,
        );
    }
}
