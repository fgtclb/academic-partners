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
        // @todo: Check how to handle hidden and deleted records in selection and existing relations
        // @todo: Check how to handle publish property of partners in selection existing relations

        $partnerRepository = GeneralUtility::makeInstance(PartnerRepository::class);
        $partnerRepository->setDefaultQuerySettings($querySettings);
        $partners = $partnerRepository->findAll();

        if ($partners !== null) {
            foreach ($partners as $partner) {
                $parameters['items'][] = [
                    'label' => $partner->getTitle(),
                    'value' => $partner->getUid(),
                    'icon' => 'tx_academicpartners_domain_model_partner',
                ];
            }
        }
    }
}
