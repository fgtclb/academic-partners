<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use FGTCLB\AcademicPartners\Task\GeocodeTask;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $academicPartnerDoktype = PageTypes::ACADEMIC_PARTNERS;
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

    if ($versionInformation->getMajorVersion() < 12) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            sprintf(
                'options.pageTree.doktypesToShowInNewPageDragArea := addToList(%d)',
                $academicPartnerDoktype
            )
        );

        // Starting with TYPO3 v12.0 Configuration/page.tsconfig in an Extension is automatically loaded during build time
        // @see https://docs.typo3.org/m/typo3/reference-tsconfig/12.4/en-us/UsingSetting/PageTSconfig.html#pagesettingdefaultpagetsconfig
        ExtensionManagementUtility::addPageTSConfig('
            @import \'EXT:academic_programs/Configuration/page.tsconfig\'
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

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][GeocodeTask::class] = [
        'extension' => 'academic_partners',
        'title' => 'Academic Partners: Geocode',
        'description' => 'Fetches geocoding information for academic partners',
    ];
})();
