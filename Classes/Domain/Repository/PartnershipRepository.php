<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Partnership>
 */
class PartnershipRepository extends Repository
{
    /**
     * @return QueryResult<Partnership>
     */
    public function findByPid(int $pid): QueryResult
    {
        $query = $this->createQuery();

        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            LanguageAspect::OVERLAYS_ON,
            $currentLanguageAspect->getFallbackChain()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->equals('page', $pid)
        );

        // The table is manually sortable (TCA ctrl `sortby`), so the order the editor
        // arranged in the backend is the one the frontend has to reproduce. Extbase does
        // not read `sortby`, and without an ORDER BY the row order belongs to the DBMS -
        // PostgreSQL returned this very query in two different orders for two renders of
        // the same data (ACE-491). `uid` settles ties deterministically. `sorting` is no
        // property of the model; the data mapper falls back to the column of that name.
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute();
    }
}
