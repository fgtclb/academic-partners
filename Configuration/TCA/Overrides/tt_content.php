<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\TcaManipulator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {
    // Plugin: academicpartners_list
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_list.title',
            'value' => 'academicpartners_list',
            'icon' => 'academic-partners',
            'group' => 'academic',
        ],
        'academic_partners'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_partners/Configuration/FlexForms/ListSettings.xml',
        'academicpartners_list',
    );

    // Plugin: academicpartners_map
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_map.title',
            'value' => 'academicpartners_map',
            'icon' => 'academic-partners',
            'group' => 'academic',
        ],
        'academic_partners'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_partners/Configuration/FlexForms/ListSettings.xml',
        'academicpartners_map',
    );

    // Add configuration tab for list and map plugins
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        implode(',', [
            '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_list.configuration',
            'pi_flexform',
            'pages',
        ]),
        implode(',', [
            'academicpartners_list',
            'academicpartners_map',
        ]),
        'after:subheader',
    );

    // Plugin: academicpartners_partnershipslist
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_partnershipslist.title',
            'value' => 'academicpartners_partnershipslist',
            'icon' => 'academic-partners',
            'group' => 'academic',
        ],
        'academic_partners'
    );

    // Plugin: academicpartners_partnershipsteaser
    (new TcaManipulator())->addContentElementPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_partnershipsteaser.title',
            'value' => 'academicpartners_partnershipsteaser',
            'icon' => 'academic-partners',
            'group' => 'academic',
        ],
        'academic_partners'
    );
})();
