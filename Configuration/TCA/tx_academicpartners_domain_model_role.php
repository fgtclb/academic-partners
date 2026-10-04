<?php

if (!defined('TYPO3')) {
    die('Not authorized');
}

return [
    'ctrl' => [
        'title' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_role',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'tx-academicpartners-record-role',
        ],
    ],
    'types' => [
        0 => [
            'showitem' => implode(',', [
                'name',
                'description',
                'partnerships',
                '--palette--;;paletteCore',
            ]),
        ],
    ],
    'palettes' => [
        'paletteCore' => [
            'showitem' => implode(',', [
                'hidden',
                'sys_language_uid',
                'l10n_parent',
                'l10n_diffsource',
            ]),
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:academic_base/Resources/Private/Language/locallang_tca.xlf:l18n_parent',
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
                'foreign_table_where' => 'AND tx_academicpartners_domain_model_role.pid=###CURRENT_PID### AND tx_academicpartners_domain_model_role.sys_language_uid IN (-1,0)',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'name' => [
            'exclude' => false,
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_role.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_role.description',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
            ],
        ],
        'partnerships' => [
            'exclude' => true,
            // A translated partnership keeps the role of its default language
            // record (the "role" select of the partnership is l10n_mode
            // "exclude"), so the list belongs to the default language role only.
            // "l10n_mode" must stay unset: with "exclude" the DataHandler
            // synchronized the list into every translation of the role on each
            // save, which wrote the uid of the role translation into the
            // translated partnerships.
            'displayCond' => 'FIELD:sys_language_uid:<=:0',
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_role.partnerships',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_academicpartners_domain_model_partnership',
                'foreign_field' => 'role',
                // A sort column of this relation's own: the partner page relation
                // owns "sorting", the column the frontend renders partnerships in.
                'foreign_sortby' => 'role_sorting',
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ],
        ],
    ],
];
