<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die;

(static function (): void {
    $ll = static fn (string $key): string => sprintf('LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:%s', $key);

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'academic',
        $ll('content.ctype.group.label'),
    );

    $contentIdentifier = 'academicpartners_list';
    // ToDo: After drop v11 Support transform first parameter to @see TYPO3\CMS\Core\Schema\Struct\SelectItem
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'label' : 0) => $ll('plugin.partner_list.title'),
            ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'value' : 1) => $contentIdentifier,
            ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'icon' : 2) => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
            ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12 ? 'group' : 3) => 'academic',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'academic_partners'
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;' . $ll('plugin.partner_list.configuration') . ',pi_flexform,',
        $contentIdentifier,
        'after:subheader',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:academic_partners/Configuration/FlexForms/ListSettings.xml',
        $contentIdentifier,
    );
})();
