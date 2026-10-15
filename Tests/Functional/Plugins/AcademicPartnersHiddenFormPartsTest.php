<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A part of the form the content element hides keeps the value of the content element:
 * a visitor who submits the visible part changes that part only (ACE-916).
 *
 * The form is submitted from the page the plugin rendered, with the fields it holds, and
 * the redirect is followed.
 *
 * Categories: Americas (2) and Europe (6) are regions, University (3) is a partner type.
 * Alpha University is a European university, Beta Institute a university in the Americas,
 * Delta Regional College is in Europe.
 *
 * - `/hidden-filter`: the category filter hidden, Europe preset, the sorting shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending, the filter shown.
 * - `/map-hidden-filter`: a map with the category filter hidden and Europe preset.
 * - `/hidden-filter-paginated`: as `/hidden-filter`, one partner per page.
 * - `/hidden-filter-no-preset`: the category filter hidden, no category preset.
 * - `/europe`: the filter and the sorting shown, Europe preset.
 */
final class AcademicPartnersHiddenFormPartsTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const MAP_NAMESPACE = 'tx_academicpartners_map';
    private const FORM = '//form[@name="demand"]';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersHiddenFormParts/partnerListPages.csv');
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
    public function sortingAListWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-filter', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '6'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Alpha University');
    }

    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-sorting', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '6']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '6'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Alpha University');
    }

    /**
     * A sorting a request carries for a list whose sorting is hidden, from an override of
     * the form or a hand-made request, does not change it.
     */
    #[Test]
    public function aSubmittedSortingDoesNotChangeAHiddenSorting(): void
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest('https://www.acme.com/hidden-sorting', [
            self::LIST_NAMESPACE => ['demand' => [
                'filterCollection' => ['region' => '6'],
                'sortingField' => 'title',
                'sortingDirection' => 'asc',
            ]],
        ]));

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('desc', $this->demandArguments($location, self::LIST_NAMESPACE)['sortingDirection'] ?? null);
        $this->assertRenderedInOrder($this->renderFrontendPage($location), 'Delta Regional College', 'Alpha University');
    }

    #[Test]
    public function aLinkedSortingDoesNotChangeAHiddenSorting(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-sorting?tx_academicpartners_list%5Bdemand%5D%5BsortingField%5D=title&tx_academicpartners_list%5Bdemand%5D%5BsortingDirection%5D=asc',
        );

        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Beta Institute');
        $this->assertRenderedInOrder($content, 'Beta Institute', 'Alpha University');
    }

    /**
     * Categories a link carries for a list whose filter is hidden narrow the list and never
     * replace a default category: Europe stays, University is added.
     */
    #[Test]
    public function aUrlNarrowsAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter?tx_academicpartners_list%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=3',
        );

        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * Without default categories, the categories of a link filter a list whose filter is
     * hidden, as they did before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter-no-preset?tx_academicpartners_list%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2',
        );

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * The site offers the partner type only. The default region of the element is no part
     * of the form, so a submitted partner type keeps it.
     */
    #[Test]
    public function aFilterKeepsTheDefaultsOfACategoryTypeItDoesNotOffer(): void
    {
        $this->addConstants('plugin.tx_academicpartners.filter.categoryTypes = partner_type');

        $response = $this->submitFrontendForm('https://www.acme.com/europe', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['partner_type' => '3']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '3,6'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * The region is part of the form, so "All" clears the default region, as before.
     */
    #[Test]
    public function aFilterStillClearsTheDefaultsOfACategoryTypeItOffers(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/europe', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '', 'partner_type' => '3']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '3'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
    }

    #[Test]
    public function sortingAMapWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/map-hidden-filter', self::FORM, [
            self::MAP_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '6'],
            $this->demandArguments($location, self::MAP_NAMESPACE)['filterCollection'] ?? null,
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('id="partner-13"', $content);
        $this->assertStringNotContainsString('id="partner-11"', $content);
    }

    /**
     * The pagination links are built from the same selection as the redirect, so the next
     * page keeps the default categories as well.
     */
    #[Test]
    public function thePagesOfAListWhoseFilterIsHiddenKeepTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-filter-paginated', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $content = $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
        $this->assertStringContainsString('Delta Regional College', $content);

        $this->assertSame(1, preg_match('#href="([^"]*currentPage%5D=2[^"]*)"#', $content, $link), 'The list renders no link to page 2.');
        $secondPage = html_entity_decode($link[1]);
        $this->assertSame(
            ['categories' => '6'],
            $this->demandArguments($secondPage, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );

        $content = $this->renderFrontendPage(rtrim(self::FRONTEND_PLUGIN_TEST_BASE, '/') . $secondPage);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
    }

    /**
     * Adds TypoScript constants to the template of the site, the way a site package sets
     * them.
     */
    private function addConstants(string $constants): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['constants' => $template['constants'] . LF . $constants], ['uid' => $template['uid']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function demandArguments(string $location, string $pluginNamespace): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? null;
        $this->assertIsArray($demand, 'The URL carries no demand: ' . $location);

        return $demand;
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $firstPosition = strpos($content, $first);
        $secondPosition = strpos($content, $second);
        $this->assertNotFalse($firstPosition, $first . ' is not rendered.');
        $this->assertNotFalse($secondPosition, $second . ' is not rendered.');
        $this->assertLessThan($secondPosition, $firstPosition, $first . ' is not rendered before ' . $second . '.');
    }
}
