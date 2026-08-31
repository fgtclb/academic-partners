<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Enumeration\SortingOptions;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * The page selection and the ordering of `PartnerRepository::findByDemand()`.
 *
 * The hidden record flag of the same method is covered by
 * `PartnerRepositoryShowHiddenRecordsTest` and is deliberately not repeated here.
 *
 * Both parts covered below come straight from the plugin FlexForm - the selected pages and
 * the sorting option an editor picks - and both are passed on unvalidated: the pages end up
 * in an `in()` constraint and the sorting option is split into a property name and a
 * direction that reach the query as they are.
 */
final class PartnerRepositoryFindByDemandTest extends AbstractAcademicPartnersTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindByDemand/partners.csv');
    }

    /**
     * An editor who selects no page in the plugin gets every partner of the installation:
     * the `pid` constraint is added only for a non empty page list, and the storage page is
     * lifted a second time inside the method. A regression here does not fail, it leaks
     * partners of unrelated page trees into a plugin that was meant to be scoped.
     */
    #[Test]
    public function allPartnersAreReturnedWhenTheDemandSelectsNoPage(): void
    {
        $this->assertSame([10, 11, 12, 13], $this->sortedResultUids($this->subject()->findByDemand($this->demand())));
    }

    #[Test]
    public function onlyThePartnersStoredOnTheSelectedPageAreReturned(): void
    {
        $demand = $this->demand();
        $demand->setPages([3]);

        $this->assertSame([10, 11], $this->sortedResultUids($this->subject()->findByDemand($demand)));
    }

    #[Test]
    public function severalSelectedPagesAreCombined(): void
    {
        $demand = $this->demand();
        $demand->setPages([3, 4]);

        $this->assertSame([10, 11, 12, 13], $this->sortedResultUids($this->subject()->findByDemand($demand)));
    }

    /**
     * A page that was selected in the plugin and later deleted leaves a uid behind that no
     * record matches. That has to end in an empty list rather than in the unscoped result of
     * the "no page selected" case above.
     */
    #[Test]
    public function aSelectedPageWithoutPartnersYieldsAnEmptyResult(): void
    {
        $demand = $this->demand();
        $demand->setPages([999]);

        $this->assertSame([], $this->sortedResultUids($this->subject()->findByDemand($demand)));
    }

    /**
     * The page selection restricts the storage page, not the page type - page 14 lives in the
     * selected folder and is an ordinary page. Only the doktype constraint keeps it out.
     */
    #[Test]
    public function anOrdinaryPageInASelectedPageIsNotReturned(): void
    {
        $demand = $this->demand();
        $demand->setPages([3]);

        $this->assertNotContains(14, $this->sortedResultUids($this->subject()->findByDemand($demand)));
    }

    /**
     * `PartnerDemand::initializeObject()` presets the default sorting option, so a demand
     * that was never touched already carries `title asc`. The list plugin renders in that
     * order, which is what an editor sees before selecting anything.
     */
    #[Test]
    public function theDefaultOrderingIsByTitleAscending(): void
    {
        $this->assertSame([11, 13, 12, 10], $this->resultUids($this->subject()->findByDemand($this->demand())));
    }

    /**
     * @return \Generator<string, array{0: string, 1: int[]}>
     */
    public static function knownSortingOptions(): \Generator
    {
        yield 'title ascending' => [SortingOptions::SORT_BY_TITLE_ASC, [11, 13, 12, 10]];
        yield 'title descending' => [SortingOptions::SORT_BY_TITLE_DESC, [10, 12, 13, 11]];
        yield 'backend sorting ascending' => [SortingOptions::SORT_BY_SORTING_ASC, [12, 10, 13, 11]];
        yield 'backend sorting descending' => [SortingOptions::SORT_BY_SORTING_DESC, [11, 13, 10, 12]];
    }

    /**
     * Every sorting option the FlexForm offers has to reach the query as an ordering the
     * database understands. `sorting` is the interesting one: `Partner` declares no such
     * property, so it only works because the data mapper falls back to the column of the
     * same name - which makes it the option most likely to break unnoticed.
     *
     * @param int[] $expectedUids
     */
    #[Test]
    #[DataProvider('knownSortingOptions')]
    public function eachSortingOptionOrdersTheResult(string $sortingOption, array $expectedUids): void
    {
        $demand = $this->demand();
        $demand->setSorting($sortingOption);

        $this->assertSame($expectedUids, $this->resultUids($this->subject()->findByDemand($demand)));
    }

    /**
     * The fixture adds three partners sharing one title to the setUp records, so the
     * demanded `title asc` ordering leaves their relative order undefined - and the DBMS
     * resolved it, differently between two calls on PostgreSQL (ACE-491). The `uid`
     * tiebreaker settles it, in the middle of an otherwise title-ordered list. The tied
     * records' `sorting` values are deliberately reversed against uid order, so a
     * `sorting`-based accident cannot produce the expected list.
     */
    #[Test]
    public function partnersEqualInTheDemandedOrderingFallBackToUidOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindByDemand/partnersWithEqualTitles.csv');
        $demand = $this->demand();
        $demand->setSorting(SortingOptions::SORT_BY_TITLE_ASC);

        $this->assertSame([11, 13, 20, 21, 22, 12, 10], $this->resultUids($this->subject()->findByDemand($demand)));
    }

    private function subject(): PartnerRepository
    {
        return $this->get(PartnerRepository::class);
    }

    private function demand(): PartnerDemand
    {
        return new PartnerDemand();
    }

    /**
     * @param QueryResult<Partner> $result
     * @return int[]
     */
    private function resultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $partner) {
            $uids[] = (int)$partner->getUid();
        }
        return $uids;
    }

    /**
     * @param QueryResult<Partner> $result
     * @return int[]
     */
    private function sortedResultUids(QueryResult $result): array
    {
        $uids = $this->resultUids($result);
        sort($uids);
        return $uids;
    }
}
