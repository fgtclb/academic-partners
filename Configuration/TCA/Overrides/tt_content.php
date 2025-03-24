<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $typo3MajorVersion = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlfcontent.ctype.group.label',
    );

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
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:plugin.partner_list.configuration,pi_flexform,',
        'academicpartners_list',
        'after:subheader',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        sprintf('FILE:EXT:academic_partners/Configuration/FlexForms/Core%s/ListSettings.xml', $typo3MajorVersion),
        'academicpartners_list',
    );
})();
