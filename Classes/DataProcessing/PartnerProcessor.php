<?php

namespace FGTCLB\AcademicPartners\DataProcessing;

use FGTCLB\AcademicPartners\Factory\PartnerFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Processor class for partner page types
 */
class PartnerProcessor implements DataProcessorInterface
{
    /**
     * Make partner data accessable in Fluid
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
        $partnerFactory = GeneralUtility::makeInstance(PartnerFactory::class);
        $processedData['partner'] = $partnerFactory->get($processedData['page']->getPageRecord());
        return $processedData;
    }
}
