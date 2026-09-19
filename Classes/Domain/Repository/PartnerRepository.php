<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
}
