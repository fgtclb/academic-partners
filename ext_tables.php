<?php

defined('TYPO3') || die();

use FGTCLB\AcademicPartners\Enumeration\PageTypes;

(static function (): void {
    $projectDokType = PageTypes::ACADEMIC_PARTNERS;

    $GLOBALS['PAGES_TYPES'][$projectDokType] = [
        'type' => 'web',
        'allowedTables' => '*',
    ];
})();
