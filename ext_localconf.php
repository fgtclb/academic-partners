<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Task\GeocodeTask;

(static function (): void {
                $academicPartnerDoktype
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'AcademicPartners',
        'List',
        [
            PartnerController::class => 'list',
        ],
        [
            PartnerController::class => 'list',
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][GeocodeTask::class] = [
        'extension' => 'academic_partners',
        'title' => 'Coop Map address to coordinates',
        'description' => '',
    ];
})();
