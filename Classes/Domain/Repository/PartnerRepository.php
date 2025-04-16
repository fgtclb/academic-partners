<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
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
            ]
        );

        return $query->execute();
    }

    public function findNextForGeolocation(): ?Partner
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('geocodeStatus', 'open')
        );
        $query->setLimit(1);
        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResult<Partner>
     */
    public function findGeoLocated(): QueryResult
    {
        $query = $this->createQuery();
        $query->matching(
            $query->in('geocodeStatus', ['successful', 'manually'])
        );
        return $query->execute();
    }
}
