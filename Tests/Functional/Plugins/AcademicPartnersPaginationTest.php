<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pagination of the partner list, without `georgringer/numbered-pagination`: the core
 * pagination links every page. The numbered variant is
 * {@see AcademicPartnersNumberedPaginationTest}.
 */
final class AcademicPartnersPaginationTest extends AbstractAcademicPartnersPaginationTestCase
{
    #[Test]
    public function theFirstPageShowsTheFirstPartnersAndANavigation(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($content));

        // Nothing filtered: the link carries no empty filter, only the sorting and the page.
        $next = $this->paginationLink($content, 'next');
        $demand = $this->demandArguments($next);
        ksort($demand);
        $this->assertSame(['currentPage' => '2', 'sortingDirection' => 'asc', 'sortingField' => 'title'], $demand);
        $this->assertSame(['Charlie College', 'Delta Academy'], $this->renderedPartners($this->renderFrontendPage($next)));
    }

    /**
     * Five European partners, two on a page: page two holds the third and the fourth, and
     * every link of the navigation keeps the region and the sorting.
     */
    #[Test]
    public function pageTwoOfAFilteredListKeepsTheFilter(): void
    {
        $filteredUrl = $this->assertSeeOtherWithCacheHash($this->submitFrontendForm(
            'https://www.acme.com/home',
            self::FORM_CLASS,
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '6']]]],
        ));
        $firstPage = $this->renderFrontendPage($filteredUrl);
        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($firstPage));

        $secondPage = $this->renderFrontendPage($this->paginationLink($firstPage, '2'));

        $this->assertSame(['Charlie College', 'Delta Academy'], $this->renderedPartners($secondPage));
        $this->assertSame(['first', 'previous', '1', '2', '3', 'next', 'last'], $this->paginationLabels($secondPage));
        $linkToPageThree = $this->demandArguments($this->paginationLink($secondPage, '3'));
        ksort($linkToPageThree);
        $this->assertSame(
            [
                'currentPage' => '3',
                'filterCollection' => ['categories' => '6'],
                'sortingDirection' => 'asc',
                'sortingField' => 'title',
            ],
            $linkToPageThree,
        );
        // The filter form shows the region the page is filtered by.
        $this->assertMatchesRegularExpression('#<option value="6"[^>]* selected="selected">Europe</option>#', $secondPage);
    }

    /**
     * On the bare page, the demand holds what the editor preselected. The link to page two
     * carries it explicitly: a URL with any demand argument makes the factory ignore the
     * preselection.
     */
    #[Test]
    public function theLinkToPageTwoCarriesThePreselectedRegion(): void
    {
        $firstPage = $this->renderFrontendPage('https://www.acme.com/europe');
        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($firstPage));

        $link = $this->paginationLink($firstPage, '2');
        $this->assertSame(['categories' => '6'], $this->demandArguments($link)['filterCollection'] ?? null);

        $this->assertSame(['Charlie College', 'Delta Academy'], $this->renderedPartners($this->renderFrontendPage($link)));
    }

    #[Test]
    public function aPageBeyondTheLastShowsTheLastPage(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?tx_academicpartners_list%5Bdemand%5D%5BsortingField%5D=title'
            . '&tx_academicpartners_list%5Bdemand%5D%5BsortingDirection%5D=asc'
            . '&tx_academicpartners_list%5Bdemand%5D%5BcurrentPage%5D=9'
        );

        $this->assertSame(['Echo School', 'Foxtrot University'], $this->renderedPartners($content));
        $this->assertSame(['first', 'previous', '1', '2', '3'], $this->paginationLabels($content));
    }

    /**
     * A filter submission starts on page one of the new selection - which may not even
     * have the page the visitor was on.
     */
    #[Test]
    public function changingTheFilterOnPageTwoStartsOnPageOne(): void
    {
        $secondPage = $this->paginationLink($this->renderFrontendPage('https://www.acme.com/home'), '2');
        $this->assertSame(['Charlie College', 'Delta Academy'], $this->renderedPartners($this->renderFrontendPage($secondPage)));

        $location = $this->assertSeeOtherWithCacheHash($this->submitFrontendForm($secondPage, self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2']]],
        ]));

        $this->assertArrayNotHasKey('currentPage', $this->demandArguments($location));
        $this->assertSame(['Foxtrot University'], $this->renderedPartners($this->renderFrontendPage($location)));
    }

    /**
     * One page only: no navigation to nowhere.
     */
    #[Test]
    public function aListThatFitsOnOnePageRendersNoNavigation(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?tx_academicpartners_list%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2'
        );

        $this->assertSame(['Foxtrot University'], $this->renderedPartners($content));
        $this->assertNull($this->paginationEntries($content));
    }

    #[Test]
    public function aListWithPaginationSwitchedOffRendersEveryPartner(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/switched-off');

        $this->assertSame(self::PARTNERS, $this->renderedPartners($content));
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * A list stored before the pagination sheet existed has no value for it at all.
     */
    #[Test]
    public function aListStoredBeforeThePaginationExistedRendersEveryPartner(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/stored-before');

        $this->assertSame(self::PARTNERS, $this->renderedPartners($content));
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unusableResultsPerPageDataProvider(): \Generator
    {
        yield 'an emptied field' => ['https://www.acme.com/results-per-page-empty'];
        yield 'zero' => ['https://www.acme.com/results-per-page-zero'];
    }

    /**
     * The paginator throws for less than one partner per page. The list takes ten
     * instead, the default of the field - here, all six partners on one page.
     */
    #[Test]
    #[DataProvider('unusableResultsPerPageDataProvider')]
    public function anUnusableResultsPerPageFallsBackToTen(string $url): void
    {
        $content = $this->renderFrontendPage($url);

        $this->assertSame(self::PARTNERS, $this->renderedPartners($content));
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * The map's stored FlexForm carries pagination values, which reach its settings -
     * Extbase reads every sheet the record holds, not only the ones its data structure
     * declares. The map draws every partner regardless.
     */
    #[Test]
    public function theMapDrawsEveryPartnerWhateverPaginationItsElementCarries(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/map');

        foreach (range(10, 15) as $partnerUid) {
            $this->assertStringContainsString('id="partner-' . $partnerUid . '"', $content);
        }
        $this->assertNull($this->paginationEntries($content));
    }

    /**
     * The core pagination has no limit on the number of page links.
     */
    #[Test]
    public function everyPageIsLinkedWithoutNumberedPagination(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['1', '2', '3', 'next', 'last'], $this->paginationLabels($content));
        $this->assertStringNotContainsString('…', implode('', $this->paginationLabels($content)));
    }
}
