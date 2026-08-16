<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `PartnerDemand` is assembled from FlexForm settings and request arguments by
 * `Factory\DemandFactory` and then handed to `PartnerRepository::findByDemand()`, where
 * the sorting reaches Extbase as an ordering. What it rejects therefore matters as much
 * as what it stores.
 */
final class PartnerDemandTest extends UnitTestCase
{
    #[Test]
    public function aFreshDemandSortsByTheDefaultOption(): void
    {
        $subject = new PartnerDemand();

        $this->assertSame(SortingOptions::__default, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame('asc', $subject->getSortingDirection());
    }

    #[Test]
    public function aFreshDemandSelectsNoPageAndNoFilter(): void
    {
        $subject = new PartnerDemand();

        $this->assertSame([], $subject->getPages());
        $this->assertNull($subject->getFilterCollection());
        $this->assertFalse($subject->getShowHiddenRecords());
    }

    #[Test]
    #[DataProvider('knownSortingOptions')]
    public function aKnownOptionIsSplitIntoFieldAndDirection(string $option, string $field, string $direction): void
    {
        $subject = new PartnerDemand();
        $subject->setSorting($option);

        $this->assertSame($option, $subject->getSorting());
        $this->assertSame($field, $subject->getSortingField());
        $this->assertSame($direction, $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function knownSortingOptions(): \Generator
    {
        yield 'title ascending' => [SortingOptions::SORT_BY_TITLE_ASC, 'title', 'asc'];
        yield 'title descending' => [SortingOptions::SORT_BY_TITLE_DESC, 'title', 'desc'];
        yield 'last updated ascending' => [SortingOptions::SORT_BY_LASTUPDATED_ASC, 'lastUpdated', 'asc'];
        yield 'last updated descending' => [SortingOptions::SORT_BY_LASTUPDATED_DESC, 'lastUpdated', 'desc'];
        yield 'backend sorting ascending' => [SortingOptions::SORT_BY_SORTING_ASC, 'sorting', 'asc'];
        yield 'backend sorting descending' => [SortingOptions::SORT_BY_SORTING_DESC, 'sorting', 'desc'];
    }

    /**
     * An unknown option leaves the demand as it was rather than clearing the ordering. A
     * stored FlexForm value that refers to a since-renamed option therefore falls back to
     * the previous sorting instead of reaching Extbase as an empty `ORDER BY`.
     *
     * The last case is the one that matters for safety: the value is used verbatim as an
     * Extbase ordering, so anything not on the allow list must never be stored.
     *
     * @param string $value the rejected value
     */
    #[Test]
    #[DataProvider('unusableSortingValues')]
    public function anUnknownOptionIsIgnored(string $value): void
    {
        $subject = new PartnerDemand();
        $subject->setSorting(SortingOptions::SORT_BY_LASTUPDATED_DESC);

        $subject->setSorting($value);

        $this->assertSame(SortingOptions::SORT_BY_LASTUPDATED_DESC, $subject->getSorting());
        $this->assertSame('lastUpdated', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unusableSortingValues(): \Generator
    {
        yield 'empty' => [''];
        yield 'field without direction' => ['title'];
        yield 'direction without field' => [' asc'];
        yield 'unknown field' => ['uid asc'];
        yield 'unknown direction' => ['title sideways'];
        yield 'wrong case' => ['TITLE ASC'];
        yield 'constant name instead of value' => ['SORT_BY_TITLE_ASC'];
        yield 'padded with whitespace' => [' title asc'];
        yield 'sql injected into the ordering' => ['title asc; DROP TABLE pages'];
    }

    /**
     * The direction is kept, so switching the column in a list view does not silently flip
     * the order back to ascending.
     */
    #[Test]
    public function changingTheFieldKeepsTheDirection(): void
    {
        $subject = new PartnerDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_DESC);

        $subject->setSortingField('lastUpdated');

        $this->assertSame(SortingOptions::SORT_BY_LASTUPDATED_DESC, $subject->getSorting());
        $this->assertSame('lastUpdated', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    #[Test]
    public function changingTheDirectionKeepsTheField(): void
    {
        $subject = new PartnerDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_ASC);

        $subject->setSortingDirection('desc');

        $this->assertSame(SortingOptions::SORT_BY_TITLE_DESC, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    /**
     * Unlike the sibling demand of `EXT:academic_programs`, every field here has both
     * directions in `SortingOptions`, so reversing the order works for each of them.
     * A dropped counterpart would turn "reverse" into a silent no-op for one column only,
     * which is the kind of thing that is never noticed in a review.
     */
    #[Test]
    #[DataProvider('sortingFields')]
    public function anyFieldCanBeReversed(string $ascending, string $descending, string $field): void
    {
        $subject = new PartnerDemand();
        $subject->setSorting($ascending);

        $subject->setSortingDirection('desc');
        $this->assertSame($descending, $subject->getSorting());
        $this->assertSame($field, $subject->getSortingField());

        $subject->setSortingDirection('asc');
        $this->assertSame($ascending, $subject->getSorting());
        $this->assertSame($field, $subject->getSortingField());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function sortingFields(): \Generator
    {
        yield 'title' => [SortingOptions::SORT_BY_TITLE_ASC, SortingOptions::SORT_BY_TITLE_DESC, 'title'];
        yield 'last updated' => [
            SortingOptions::SORT_BY_LASTUPDATED_ASC,
            SortingOptions::SORT_BY_LASTUPDATED_DESC,
            'lastUpdated',
        ];
        yield 'backend sorting' => [
            SortingOptions::SORT_BY_SORTING_ASC,
            SortingOptions::SORT_BY_SORTING_DESC,
            'sorting',
        ];
    }

    /**
     * There is no option without a direction, so the direction cannot be cleared. Worth
     * pinning: it means `getSortingDirection()` never returns an empty string once the
     * constructor has run, which is what lets the repository use it unchecked.
     */
    #[Test]
    public function theDirectionCannotBeCleared(): void
    {
        $subject = new PartnerDemand();

        $subject->setSortingDirection('');

        $this->assertSame('asc', $subject->getSortingDirection());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, $subject->getSorting());
    }

    /**
     * Same for the field: an unknown column never reaches the ordering, not even by way of
     * `setSortingField()` reassembling it with a valid direction.
     */
    #[Test]
    public function anUnknownFieldIsRejected(): void
    {
        $subject = new PartnerDemand();

        $subject->setSortingField('uid');

        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, $subject->getSorting());
    }

    /**
     * The pages list reaches `findByDemand()` as an uid list for an `IN` constraint, and an
     * empty one is valid there since ACE-349 - so the demand must not invent a value.
     */
    #[Test]
    public function thePageListIsStoredAsGiven(): void
    {
        $subject = new PartnerDemand();
        $subject->setPages([12, 34]);

        $this->assertSame([12, 34], $subject->getPages());

        $subject->setPages([]);

        $this->assertSame([], $subject->getPages());
    }

    /**
     * The category filter is optional, and clearing it has to be possible - the repository
     * branches on `null` to skip the category constraint entirely.
     */
    #[Test]
    public function theFilterCollectionCanBeSetAndClearedAgain(): void
    {
        $subject = new PartnerDemand();
        $filterCollection = new FilterCollection();

        $subject->setFilterCollection($filterCollection);
        $this->assertSame($filterCollection, $subject->getFilterCollection());

        $subject->setFilterCollection(null);
        $this->assertNull($subject->getFilterCollection());
    }
}
