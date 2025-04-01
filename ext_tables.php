<?php

defined('TYPO3') || die();

use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    GeneralUtility::makeInstance(PageDoktypeRegistry::class)
        ->add(PageTypes::ACADEMIC_PARTNERS, [
            'allowedTables' => '*',
        ]);
})();
