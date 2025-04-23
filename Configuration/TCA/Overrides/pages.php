<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Backend\FormEngine\CountryItems;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die;

(static function (): void {
    // Add academic option group to doktype select
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'pages',
        'doktype',
        'academic',
        'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:pages.doktype.groups.academic',
        'after:default'
    );

    // Add academic partners doktype to doktype select
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:pages.doktype.items.academic_partner',
            'value' => PageTypes::ACADEMIC_PARTNERS,
            'icon' => 'academic-partners',
            'group' => 'academic',
        ]
    );

    // Add type and typeicon
    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages'],
        [
            'ctrl' => [
                'typeicon_classes' => [
                    PageTypes::ACADEMIC_PARTNERS => 'academic-partners',
                ],
            ],
            'types' => [
                PageTypes::ACADEMIC_PARTNERS => [
                    'showitem' => $GLOBALS['TCA']['pages']['types'][PageRepository::DOKTYPE_DEFAULT]['showitem'],
                ],
            ],
        ]
    );

    // Define academic partners specific columns
    $additionalTCAcolumns = [
        'abbreviation' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.abbreviation.label',
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.description.label',
            'config' => [
                'type' => 'text',
                'rows' => 5,
            ],
        ],
        'link' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.link.label',
            'exclude' => true,
            'config' => [
                'type' => 'link',
                // @todo Only 255 ? Does this make sense ?
                'max' => 255,
                // @todo Is narrowing down `allowedTypes` required in some way ?
                // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-97159-NewTCATypeLink.html
            ],
        ],
        'address_street' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_street.label',
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'address_street_number' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_street_number.label',
            'config' => [
                'type' => 'input',
                'max' => 8,
                'eval' => 'trim',
            ],
        ],
        'address_zip' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_zip.label',
            'config' => [
                'type' => 'input',
                'max' => 16,
                'eval' => 'trim',
            ],
        ],
        'address_city' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_city.label',
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'address_country' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_country.label',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_country.I.default.label',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => CountryItems::class . '->itemsProcFunc',
            ],
        ],
        'address_additional' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.address_additional.label',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
        'geocode_longitude' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_longitude.label',
            'config' => [
                'type' => 'input',
                'max' => 20,
                'eval' => 'trim',
            ],
        ],
        'geocode_latitude' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_latitude.label',
            'config' => [
                'type' => 'input',
                'max' => 20,
                'eval' => 'trim',
            ],
        ],
        'geocode_last_run' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_last_run.label',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
            ],
        ],
        'geocode_status' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_status.label',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_status.I.open.label',
                        'value' => 'open',
                    ],
                    [
                        'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_status.I.successful.label',
                        'value' => 'successful',
                    ],
                    [
                        'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_status.I.failed.label',
                        'value' => 'failed',
                    ],
                    [
                        'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_status.I.manually.label',
                        'value' => 'manually',
                    ],
                ],
                'default' => 'open',
            ],
        ],
        'geocode_message' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.geocode_message.label',
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'show_on_map' => [
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.show_on_map.label',
            'config' => [
                'type' => 'check',
                'default' => true,
            ],
        ],
        'tx_academicpartners_partnerships' => [
            'exclude' => true,
            'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.tx_academicpartners_partnerships.label',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'showNewRecordLink' => true,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'top',
                    'useCombination' => false,
                    'suppressCombinationWarning' => false,
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => false,
                    'showAllLocalizationLink' => false,
                    'showSynchronizationLink' => false,
                    'enabledControls' => [
                        'info' => true,
                        'new' =>  true,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                    'showPossibleRecordsSelector' => false,
                    'elementBrowserEnabled' => false,
                ],
                'behavior' => [
                    'enableCascadingDelete' => true,
                ],
                'foreign_field' =>  'page',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_academicpartners_domain_model_partnership',
            ],
        ],
    ];

    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages']['palettes'],
        [
            'address' => [
                'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:palettes.address.label',
                'showitem' => implode(',', [
                    'address_street',
                    'address_street_number',
                    '--linebreak--',
                    'address_zip',
                    'address_city',
                    '--linebreak--',
                    'address_country',
                    '--linebreak--',
                    'address_additional',
                ]),
            ],
            'geocode' => [
                'label' => 'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:palettes.geocode.label',
                'showitem' => implode(',', [
                    'geocode_longitude',
                    'geocode_latitude',
                    '--linebreak--',
                    'geocode_last_run',
                    'geocode_status',
                    '--linebreak--',
                    'geocode_message',
                    'show_on_map',
                ]),
            ],
        ]
    );

    ExtensionManagementUtility::addTCAcolumns(
        'pages',
        $additionalTCAcolumns
    );

    // Add the partnerships tab and column to all page types
    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        implode(',', [
            '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership',
            'tx_academicpartners_partnerships',
        ]),
        '',
        'after:title'
    );

    // Add all other columns only to academic partners page type
    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        implode(',', [
            '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:pages.div.partner_information',
            '--palette--;;address',
            '--palette--;;geocode',
        ]),
        (string)PageTypes::ACADEMIC_PARTNERS,
        'after:title'
    );
})();
