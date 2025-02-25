<?php

defined('TYPO3') || die('Access denied.');

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'AcademicPartners',
        'List',
        'Academic Partners'
    );
})();
