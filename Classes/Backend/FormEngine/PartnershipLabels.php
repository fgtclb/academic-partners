<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Backend\FormEngine;

use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class PartnershipLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        // A record the backend cannot load comes without a uid, a new one with its
        // `NEW…` placeholder, which an integer column comparison rejects on PostgreSQL.
        $uid = $parameters['row']['uid'] ?? null;
        if (!MathUtility::canBeInterpretedAsInteger($uid) || (int)$uid <= 0) {
            return;
        }

        $partnershipRepository = GeneralUtility::makeInstance(PartnershipRepository::class);
        $partnership = $partnershipRepository->findByUid((int)$uid);

        if ($partnership) {
            $parameters['title'] = $partnership->getLabel();
        }
    }
}
