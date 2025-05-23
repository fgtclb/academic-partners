<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $typo3MajorVersion = (new Typo3Version())->getMajorVersion();

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label',
    );

    // Plugin: academicpartners_list
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_list.title',
            'value' => 'academicpartners_list',
            'icon' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_partners'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        sprintf('FILE:EXT:academic_partners/Configuration/FlexForms/Core%s/ListSettings.xml', $typo3MajorVersion),
        'academicpartners_list',
    );

    // Plugin: academicpartners_map
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_map.title',
            'value' => 'academicpartners_map',
            'icon' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_partners'
    );
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        sprintf('FILE:EXT:academic_partners/Configuration/FlexForms/Core%s/ListSettings.xml', $typo3MajorVersion),
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
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_partnershipslist.title',
            'value' => 'academicpartners_partnershipslist',
            'icon' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_partners'
    );

    // Plugin: academicpartners_partnershipsteaser
    ExtensionManagementUtility::addPlugin(
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_partnershipsteaser.title',
            'value' => 'academicpartners_partnershipsteaser',
            'icon' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
            'group' => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_partners'
    );
})();
