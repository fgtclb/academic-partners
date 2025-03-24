<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Task\GeocodeTask;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

    // Starting with TYPO3 v13.0 Configuration/user.tsconfig in an Extension is automatically loaded during build time
    // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.html
    if ($versionInformation->getMajorVersion() < 13) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
            @import \'EXT:academic_partners/Configuration/user.tsconfig\'
        ');
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicPartners',
        'List',
        [
            PartnerController::class => 'list',
        ],
        [
            PartnerController::class => 'list',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    if (ExtensionManagementUtility::isLoaded('scheduler')) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][GeocodeTask::class] = [
            'extension' => 'academic_partners',
            'title' => 'Academic Partners: Geocode',
            'description' => 'Fetches geocoding information for academic partners',
        ];
    }
})();
