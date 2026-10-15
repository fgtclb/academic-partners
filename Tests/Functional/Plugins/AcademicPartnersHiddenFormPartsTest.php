<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * A part of the form the content element hides keeps the value of the content element:
 * a visitor who submits the visible part changes that part only (ACE-916).
 *
 * The form posts to the page, and the list renders in the answer to the post. A post carries
 * the fields of the parts the form shows, a hidden part has no field.
 *
 * Categories: Americas (2) and Europe (6) are regions, University (3) is a partner type.
 * Alpha University is a European university, Beta Institute a university in the Americas,
 * Delta Regional College is in Europe.
 *
 * - `/hidden-filter`: the category filter hidden, Europe preset, the sorting shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending, the filter shown.
 * - `/map-hidden-filter`: a map with the category filter hidden and Europe preset.
 * - `/hidden-filter-no-defaults`: the category filter hidden, no default categories.
 * - `/visible-filter`: the category filter shown, Europe preset.
 */
final class AcademicPartnersHiddenFormPartsTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const MAP_NAMESPACE = 'tx_academicpartners_map';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
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
        $content = $this->postDemand('https://www.acme.com/hidden-filter', self::LIST_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'desc',
        ]);

        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Alpha University');
    }

    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $content = $this->postDemand('https://www.acme.com/hidden-sorting', self::LIST_NAMESPACE, [
            'filterCollection' => ['region' => '6', 'partner_type' => ''],
        ]);

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
        $content = $this->postDemand('https://www.acme.com/hidden-sorting', self::LIST_NAMESPACE, [
            'filterCollection' => ['region' => '6', 'partner_type' => ''],
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
        ]);

        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Alpha University');
    }

    /**
     * Categories a link or a hand-made URL carries for a list whose filter is hidden are
     * added to the default categories: they narrow the list to the European universities.
     */
    #[Test]
    public function aUrlNarrowsTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 2, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '3']]],
        ]));

        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * A URL never replaces a default category of a hidden filter: no partner is in Europe
     * and in the Americas at once.
     */
    #[Test]
    public function aUrlDoesNotReplaceTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 2, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '2']]],
        ]));

        $this->assertStringContainsString('academic-partners-list', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * A list that hides its filter without default categories is filtered by the
     * categories of a link, as before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter-no-defaults', 6, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '2']]],
        ]));

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    /**
     * A list that shows its filter works as before: the selection of the visitor replaces
     * the default categories.
     */
    #[Test]
    public function aSelectionInAVisibleFilterReplacesTheDefaultCategories(): void
    {
        $content = $this->postDemand('https://www.acme.com/visible-filter', self::LIST_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
            'filterCollection' => ['region' => '2', 'partner_type' => ''],
        ]);

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
    }

    #[Test]
    public function sortingAMapWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $content = $this->postDemand('https://www.acme.com/map-hidden-filter', self::MAP_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'desc',
        ]);

        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('id="partner-13"', $content);
        $this->assertStringNotContainsString('id="partner-11"', $content);
    }

    /**
     * The page as the answer to a post of the filter form renders it.
     *
     * @param array<string, mixed> $demand
     */
    private function postDemand(string $url, string $pluginNamespace, array $demand): string
    {
        $parsedBody = [$pluginNamespace => ['demand' => $demand]];
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return $this->renderFrontendPage(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body)
                ->withParsedBody($parsedBody),
        );
    }

    /**
     * The URL of a page with plugin arguments and the cache hash a link carries: without
     * one, the page answers `404`.
     *
     * @param array<string, mixed> $arguments
     */
    private function pageUrl(string $path, int $pageId, array $arguments): string
    {
        $query = HttpUtility::buildQueryString($arguments);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return 'https://www.acme.com' . $path . '?' . $query . '&cHash=' . $cacheHash;
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
