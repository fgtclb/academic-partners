<?php

namespace FGTCLB\AcademicPartners\DataProcessing;

use FGTCLB\AcademicPartners\Factory\PartnerFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Processor class for partner page types
 *
 * Adds the variables `partner` and `mapSettings`. `mapSettings` are the values of the
 * option `map`, the settings a page template hands to the partial `Partner/Map`. They
 * go through the processor rather than through `settings`, which a PAGEVIEW page object
 * does not read.
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
        // Try to fetch page data for FLUIDTEMPLATE
        $pageData = $processedData['data'] ?? [];
        if ($pageData === []) {
            // If no page data is available in FLUIDTEMPLATE, try to fetch page data from PAGEVIEW
            $pageData = $processedData['page']->getPageRecord() ?? [];
        }
        if ($pageData !== []) {
            $programDataFactory = GeneralUtility::makeInstance(PartnerFactory::class);
            $processedData['partner'] = $programDataFactory->get($pageData);
        }
        $mapSettings = [];
        foreach ($processorConfiguration['map.'] ?? [] as $name => $value) {
            if (is_string($value)) {
                $mapSettings[$name] = $value;
            }
        }
        $processedData['mapSettings'] = $mapSettings;
        return $processedData;
    }
}
