<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {
    $ll = static fn (string $key): string => sprintf('LLL:EXT:academic_partners/Resources/Private/Language/locallang.xlf:%s', $key);

    // Add doktype to select
    $doktype = PageTypes::ACADEMIC_PARTNER;

    // Add academic option group to doktype select
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'pages',
        'doktype',
        'academic',
        $ll('pages.doktype.groups.academic'),
        'after:default'
    );

    // Add academic partners doktype to doktype select
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            $ll('pages.doktype.items.academic_partner'),
            $doktype,
            'academic-partners',
            'academic',
        ]
    );

    // Add type and typeicon
    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    $doktype => 'academic-partners',
                ],
            ],
            'types' => [
                $doktype => [
                    'showitem' => $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'],
                ],
            ],
        ]
    );

    // Define academic partners specific columns
    $additionalTCAcolumns = [
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
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'default' => 0,
                    'items' => [
                        ['', 0],
                    ],
                    'foreign_table' => 'tx_academicpartners_domain_model_partner',
                    'foreign_table_where' => 'AND {#tx_academicpartners_domain_model_partner}.{#pid}=###CURRENT_PID### AND {#tx_academicpartners_domain_model_partner}.{#sys_language_uid} IN (-1,0)',
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
                ],
            ],
            'hidden' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        [
                            0 => '',
                            'invertStateDisplay' => true,
                        ],
                    ],
                ],
            ],
            'starttime' => [
                'exclude' => true,
                'l10n_display' => 'defaultAsReadonly',
                'l10n_mode' => 'exclude',
                'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime,int',
                    'default' => 0,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'endtime' => [
                'exclude' => true,
                'l10n_display' => 'defaultAsReadonly',
                'l10n_mode' => 'exclude',
                'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime,int',
                    'default' => 0,
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
            ],
            'cruser_id' => [
                'label' => 'cruser_id',
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'name' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.name.label',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim,required',
                ],
            ],
            'abbreviation' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.abbreviation.label',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim',
                    'default' => '',
                ],
            ],
            'description' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.description.label',
                'config' => [
                    'type' => 'text',
                    'rows' => 5,
                ],
            ],
            'image' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.image.label',
                'exclude' => true,
                'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                    'image',
                    [
                        'appearance' => [
                            'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                        ],
                        'overrideChildTca' => [
                            'types' => [
                                '0' => [
                                    'showitem' => '
                                    --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                                \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                                    'showitem' => '
                                    --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                                \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                    'showitem' => '
                                    --palette--;;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                                \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                                    'showitem' => '
                                    --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                                \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                                    'showitem' => '
                                    --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                                \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                                    'showitem' => '
                                    --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette',
                                ],
                            ],
                        ],
                        'foreign_match_fields' => [
                            'fieldname' => 'image',
                            'tablenames' => 'tx_academicpartners_domain_model_partner',
                            'table_local' => 'sys_file',
                        ],
                        'maxitems' => 1,
                    ],
                    $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
                ),
            ],
            'link' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.link.label',
                'exclude' => true,
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputLink',
                    'max' => 255,
                ],
            ],
            'street' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.street.label',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim',
                ],
            ],
            'street_number' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.street_number.label',
                'config' => [
                    'type' => 'input',
                    'max' => 8,
                    'eval' => 'trim',
                ],
            ],
            'zip' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.zip.label',
                'config' => [
                    'type' => 'input',
                    'max' => 16,
                    'eval' => 'trim',
                ],
            ],
            'city' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.city.label',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim',
                ],
            ],
            'country' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.country.label',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            $ll . 'tx_academicpartners_domain_model_partner.columns.country.I.default.label',
                            '',
                        ],
                    ],
                    'itemsProcFunc' => FGTCLB\AcademicPartners\Backend\FormEngine\CountryItems::class . '->itemsProcFunc',
                ],
            ],
            'longitude' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.longitude.label',
                'config' => [
                    'type' => 'input',
                    'max' => 20,
                    'eval' => 'trim',
                ],
            ],
            'latitude' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.latitude.label',
                'config' => [
                    'type' => 'input',
                    'max' => 20,
                    'eval' => 'trim',
                ],
            ],
            'last_geocoded' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.last_geocoded.label',
                'config' => [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime,int',
                ],
            ],
            'geocode_status' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_status.label',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_status.I.open.label',
                            'open',
                        ],
                        [
                            $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_status.I.successful.label',
                            'successful',
                        ],
                        [
                            $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_status.I.failed.label',
                            'failed',
                        ],
                        [
                            $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_status.I.manually.label',
                            'manually',
                        ],
                    ],
                    'default' => 'open',
                ],
            ],
            'geocode_message' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.geocode_message.label',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'eval' => 'trim',
                ],
            ],
            'map_show' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.map_show.label',
                'config' => [
                    'type' => 'check',
                    'default' => true,
                ],
            ],
            'additional' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.columns.additional.label',
                'config' => [
                    'type' => 'text',
                    'cols' => 60,
                    'rows' => 5,
                ],
            ],
            'categories' => [
                'config' => [
                    'type' => 'category',
                ],
            ],
        ],
        'palettes' => [
            'general' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.palettes.general.label',
                'showitem' => implode(',', [
                    'name',
                    'abbreviation',
                    '--linebreak--',
                    'description',
                    '--linebreak--',
                    'image',
                    '--linebreak--',
                    'link',
                ]),
            ],
            'address' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.palettes.address.label',
                'showitem' => implode(',', [
                    'street',
                    'street_number',
                    '--linebreak--',
                    'zip',
                    'city',
                    '--linebreak--',
                    'country',
                    '--linebreak--',
                    'additional',
                ]),
            ],
            'geodata' => [
                'label' => $ll . 'tx_academicpartners_domain_model_partner.palettes.geodata.label',
                'showitem' => implode(',', [
                    'longitude',
                    'latitude',
                    '--linebreak--',
                    'last_geocoded',
                    'geocode_status',
                    '--linebreak--',
                    'geocode_message',
                    'map_show',
                ]),
            ],
            'language' => [
                'showitem' => implode(',', [
                    'sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel',
                    'l10n_parent',
                ]),
            ],
            'hidden' => [
                'showitem' => 'hidden',
            ],
            'access' => [
                'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
                'showitem' => implode(',', [
                    'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
                    'endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
                ]),
            ],
        ],
        'types' => [
            '1' => [
                'showitem' => implode(',', [
                    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                    '--palette--;;general',
                    '--div--;' . $ll . 'tx_academicpartners_domain_model_partner.tabs.address.label',
                    '--palette--;;address',
                    '--palette--;;geodata',
                    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories',
                    'categories',
                    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language',
                    '--palette--;;language',
                    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
                    '--palette--;;hidden',
                    '--palette--;;access',
                ]),
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns(
        'pages',
        $additionalTCAcolumns
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        '--div--;'
            . $ll('pages.div.partner_information')
            . ','
            . implode(',', [
                'credit_points',
                'job_profile',
                'performance_scope',
                'prerequisites',
            ]),
        (string)$doktype,
        'after:title'
    );
})();
