<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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

        return $query->execute();
    }
}
