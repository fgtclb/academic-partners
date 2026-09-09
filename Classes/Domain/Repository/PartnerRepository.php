<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Partner>
 */
class PartnerRepository extends Repository
{
    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @return QueryResult<Partner>
     */
    public function findAll(): ?QueryResult
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('doktype', PageTypes::ACADEMIC_PARTNERS)
        );
        // Partner records are pages, so `sorting` is the backend order the editor
        // arranged - among siblings it is the page tree order, and partners spread over
        // several parent pages interleave deterministically by the same value. Without
        // an ORDER BY the order belongs to the DBMS and can change between two calls
        // (ACE-491); `uid` settles ties deterministically.
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);
        return $query->execute();
    }

    /**
     * @return QueryResult<Partner>
     * @throws InvalidEnumerationValueException
     */
    public function findByDemand(PartnerDemand $demand): QueryResult
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($demand->getShowHiddenRecords() === true) {
            // Include hidden (disabled) records; other enable fields
            // (deleted, start-/endtime, fe_group) stay in effect.
            $query->getQuerySettings()->setIgnoreEnableFields(true);
            $query->getQuerySettings()->setEnableFieldsToBeIgnored(['disabled']);
        }

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::ACADEMIC_PARTNERS);

        if (!empty($demand->getPages())) {
            $constraints[] = $query->in('pid', $demand->getPages());
        }

        if ($demand->getDrawableOnly() === true) {
            $constraints[] = $this->drawableCoordinatesConstraint($query);
        }

        if ($demand->getFilterCollection() !== null) {
            foreach ($demand->getFilterCollection()->getFilterCategories() as $category) {
                $constraints[] = $query->contains('categories', $category->getUid());
            }
        }

        // The method signature of logicalAnd and logicalOr has changed in TYPO3 v12
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96044-HardenMethodSignatureOfLogicalAndAndLogicalOr.html
        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );

        $query->setOrderings(
            [
                $demand->getSortingField() => strtoupper($demand->getSortingDirection()),
                // Records equal in the demanded ordering would otherwise follow the DBMS
                // row order, which is not the same list twice (ACE-491). None of the
                // `SortingOptions` sorts by `uid`, so the tiebreaker never collides.
                'uid' => QueryInterface::ORDER_ASCENDING,
            ]
        );

        return $query->execute();
    }

    public function findNextForGeolocation(): ?Partner
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::ACADEMIC_PARTNERS);
        $constraints[] = $query->equals('geocodeStatus', 'open');

        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );

        // A limit of one without an ordering lets the DBMS pick the record, so which
        // partner gets geocoded next was arbitrary (ACE-491). Oldest first makes the
        // queue deterministic and keeps a fresh record from starving older ones.
        $query->setOrderings([
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResult<Partner>
     */
    public function findGeoLocated(): QueryResult
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('doktype', PageTypes::ACADEMIC_PARTNERS);
        $constraints[] = $query->in('geocodeStatus', ['successful', 'manually']);
        // A status alone is not a coordinate: a row can claim to be located and still
        // carry none. Both halves have to hold for the record to be drawable.
        $constraints[] = $this->drawableCoordinatesConstraint($query);

        $query->matching(
            $query->logicalAnd(...array_values($constraints))
        );

        // Same backend sorting order as `findAll()` - see there (ACE-491).
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute();
    }

    /**
     * Both coordinate columns are nullable `VARCHAR(20)` while `Partner` types them as
     * non-nullable `float`, so a partner that was never geocoded - or whose geocoding
     * failed - reaches the template as the perfectly valid coordinate `0`. On the map
     * that is a marker off the coast of Africa (ACE-562).
     *
     * Absence is the first criterion: `NULL` and the empty string mean "no coordinate".
     * `NOT (column = '')` is `NULL` for a `NULL` column and therefore not true, so one
     * clause per column excludes both without a second comparison.
     *
     * A stored `0` is a real coordinate and is kept - longitude 0 runs through the
     * United Kingdom, France, Spain and Ghana, and latitude 0 is the equator. Only the
     * pair `0/0`, which is open ocean and in practice means "nothing was written", is
     * excluded.
     *
     * That pair is matched as a **string**, and only in the spelling the command
     * writes: `GeocodeCommand` persists a `float` and `DataMapper::getPlainValue(0.0)`
     * yields `"0"`. A hand-entered `0.0` is not caught here and is dropped by the
     * frontend module instead, which parses the value. The two rules are therefore
     * asymmetric on purpose: a numeric comparison cannot be expressed portably while
     * the column is `VARCHAR` - Extbase's QOM has no cast, `column + 0` is a type
     * error on PostgreSQL, and `CAST` names its target type differently per platform.
     * ACE-566 changes the column to a numeric type and removes the asymmetry.
     *
     * `Partner::isDrawable()` applies the same pair rule to a single object, for
     * templates that have no query to constrain. It does not see what this constraint
     * sees, in either direction: an absent coordinate has become `0` by the time the
     * model holds it, while a stored `0.0` - which this string comparison lets through -
     * is refused there. The method documents both.
     *
     * One residual difference: MariaDB and MySQL compare `CHAR` with `PAD SPACE`
     * semantics, so a coordinate of only spaces equals `''` there and does not on
     * PostgreSQL or SQLite. The TCA `eval => 'trim'` keeps that out of reach of the
     * backend, and only a raw import could produce it.
     *
     * @param QueryInterface<Partner> $query
     */
    private function drawableCoordinatesConstraint(QueryInterface $query): ConstraintInterface
    {
        return $query->logicalAnd(
            $query->logicalNot($query->equals('geocodeLatitude', '')),
            $query->logicalNot($query->equals('geocodeLongitude', '')),
            $query->logicalNot(
                $query->logicalAnd(
                    $query->equals('geocodeLatitude', '0'),
                    $query->equals('geocodeLongitude', '0'),
                )
            ),
        );
    }
}
