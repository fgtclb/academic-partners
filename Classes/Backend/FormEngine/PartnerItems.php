<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Backend\FormEngine;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

class PartnerItems
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        // Offer every partner page that is not deleted, hidden ones and those outside
        // their start or end time included. The record selects of the core apply no
        // enable fields either. An editor can prepare a partnership before the partner
        // page goes live, and a stored partner stays among the items, which a select
        // without `foreign_table` needs to keep it on save. Set here because the
        // backend default of `Typo3QuerySettings` depends on the global request, and
        // on TYPO3 v12 on a deprecated Extbase feature toggle as well.
        $querySettings->setIgnoreEnableFields(true);

        $partnerRepository = GeneralUtility::makeInstance(PartnerRepository::class);
        $partnerRepository->setDefaultQuerySettings($querySettings);
        $partners = $partnerRepository->findAll();

        if ($partners !== null) {
            foreach ($partners as $partner) {
                $parameters['items'][] = [
                    'label' => $partner->getTitle(),
                    'value' => $partner->getUid(),
                    'icon' => 'tx_academicpartners_domain_model_partnership',
                ];
            }
        }
    }
}
