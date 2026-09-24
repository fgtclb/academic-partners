<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Hook\PartnershipSortingHook;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    ExtensionUtility::configurePlugin(
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

    ExtensionUtility::configurePlugin(
        'AcademicPartners',
        'Map',
        [
            PartnerController::class => 'map',
        ],
        [
            PartnerController::class => 'map',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        'AcademicPartners',
        'PartnershipsList',
        [
            PartnerController::class => 'partnershipsList',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        'AcademicPartners',
        'PartnershipsTeaser',
        [
            PartnerController::class => 'partnershipsTeaser',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['academicPartnersPartnershipSorting']
        = PartnershipSortingHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['academicPartnersPartnershipSorting']
        = PartnershipSortingHook::class;

    // The list actions are not cacheable, so the cached page around them never depends on
    // the demand. Kept out of the cache hash, every filter URL of a list shares that one page
    // cache entry, rather than the redirect of a filter submission signing one entry per
    // combination of categories and sorting that anybody cares to submit.
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicpartners_list[demand]';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^tx_academicpartners_map[demand]';
})();
