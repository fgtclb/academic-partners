<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Controller\PartnerController;
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
})();
