<?php

use FGTCLB\AcademicPartners\Backend\FormEngine\PartnerItems;
use FGTCLB\AcademicPartners\Backend\FormEngine\PartnershipLabels;

if (!defined('TYPO3')) {
    die('Not authorized');
}

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership',
        'label' => 'uid',
        'label_userFunc' => PartnershipLabels::class . '->getTitle',
        'hideTable' => false,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'typeicon_classes' => [
            'default' => 'tx_academicpartners_domain_model_partnership',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        0 => [
            'showitem' => implode(',', [
                'page',
                'partner',
                'role',
                'hidden',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                'sys_language_uid',
                'l10n_parent',
            ]),
        ],
    ],
    'columns' => [
        // Declared rather than left to the core. TYPO3 v13 creates a missing
        // "transOrigPointerField" column as the select below, TYPO3 v12 adds it as
        // "passthrough". A "NEW" placeholder in a passthrough language pointer is
        // written with the value the remap stack of DataHandler computed for the
        // entry before it, so on v12 a translation created together with its
        // original pointed at an unrelated record (ACE-854).
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_academicpartners_domain_model_partnership',
                'foreign_table_where' => 'AND {#tx_academicpartners_domain_model_partnership}.{#pid}=###CURRENT_PID### AND {#tx_academicpartners_domain_model_partnership}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'page' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership.page',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'maxitems' => 1,
                'minitems' => 1,
                'size' => 1,
                'default' => 0,
            ],
        ],
        'partner' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership.partner',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        // @todo empty labels does not make sense, they are not really selectable. Consider to a defaultlike `-n/a-` or `- please choose -`
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'itemsProcFunc' => PartnerItems::class . '->itemsProcFunc',
                'sortItems' => [
                    'label' => 'asc',
                ],
                'minitems' => 1,
                'default' => 0,
            ],
        ],
        'role' => [
            'exclude' => false,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership.role',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        // @todo empty labels does not make sense, they are not really selectable. Consider to a defaultlike `-n/a-` or `- please choose -`
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_academicpartners_domain_model_role',
                'foreign_table_where' => 'AND {#tx_academicpartners_domain_model_role}.{#sys_language_uid} = 0 ORDER BY tx_academicpartners_domain_model_role.name ASC',
                'minitems' => 1,
                'default' => 0,
            ],
        ],
    ],
];
