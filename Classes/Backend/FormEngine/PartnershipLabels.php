<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Backend\FormEngine;

use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PartnershipLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        if (!isset($parameters['row']) && !isset($parameters['row']['uid'])) {
            return;
        }

        $partnershipRepository = GeneralUtility::makeInstance(PartnershipRepository::class);
        $partnership = $partnershipRepository->findByUid($parameters['row']['uid']);

        if ($partnership) {
            $parameters['title'] = $partnership->getLabel();
        }
    }
}
