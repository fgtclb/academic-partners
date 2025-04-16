<?php

namespace FGTCLB\AcademicPartners\DataProcessing;

use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class PartnershipProcessor implements DataProcessorInterface
{
    /**
     * Make project data accessable in Fluid
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        [$currentRecordTable, $currentRecordUid] = explode(':', $cObj->currentRecord);
        if ($currentRecordTable !== 'pages') {
            return $processedData;
        }

        $partnershipRepository = GeneralUtility::makeInstance(PartnershipRepository::class);
        $partnerships = $partnershipRepository->findByPid((int)$currentRecordUid);

        $processedData['partnerships'] = $partnerships;

        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }
        $processedData['partnershipRoles'] = $roles;

        return $processedData;
    }
}
