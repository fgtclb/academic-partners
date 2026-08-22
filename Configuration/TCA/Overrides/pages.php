<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\TcaManipulator;
use FGTCLB\AcademicPartners\Backend\FormEngine\CountryItems;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
                    // TYPO3 v14+ resolves the tables allowed on a page type from
                    // this TCA option (superseding the PageDoktypeRegistry below).
                    'allowedRecordTypes' => ['*'],
                ],
            ],
        ]
    );

    // TYPO3 v13 has no "allowedRecordTypes" TCA option yet and still resolves the
    // allowed tables through the PageDoktypeRegistry. Registering it in
    // ext_tables.php was deprecated in TYPO3 v14.3, hence the version-guarded call.
    // @todo Remove once TYPO3 v13 support is dropped; keep only "allowedRecordTypes".
    if ((new Typo3Version())->getMajorVersion() < 14) {
        GeneralUtility::makeInstance(PageDoktypeRegistry::class)->add(
            PageTypes::ACADEMIC_PARTNERS,
            ['allowedTables' => '*']
        );
    }

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

    $GLOBALS['TCA'] = GeneralUtility::makeInstance(TcaManipulator::class)->addToPageTypesGeneralTab(
        $GLOBALS['TCA'],
        implode(',', [
            '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:tx_academicpartners_domain_model_partnership',
            'tx_academicpartners_partnerships',
        ]),
        [],
        [254, 255]
    );

    $GLOBALS['TCA'] = GeneralUtility::makeInstance(TcaManipulator::class)->addToPageTypesGeneralTab(
        $GLOBALS['TCA'],
        implode(',', [
            '--div--;LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:pages.div.partner_information',
            '--palette--;;address',
            '--palette--;;geocode',
        ]),
        [PageTypes::ACADEMIC_PARTNERS]
    );

    //==================================================================================================================
    // Page TSconfig, selectable in the page field "Page TSconfig" for installations that do not use site sets.
    //
    // The files are the same ones the sets of this extension deliver. Use one mechanism per site, not both.
    //
    // The page type registered above and its backend layout are deliberately NOT part of this: they are stored on page
    // records, so they have to resolve on every installation. The page type is registered in TCA and the backend layout
    // is imported by the always included "Configuration/page.tsconfig".
    //==================================================================================================================
    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_partners',
        'Configuration/TSconfig/List/page.tsconfig',
        'Academic Partners: Partners List',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_partners',
        'Configuration/TSconfig/Map/page.tsconfig',
        'Academic Partners: Partners Map',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_partners',
        'Configuration/TSconfig/PartnershipsList/page.tsconfig',
        'Academic Partners: Partnerships List',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_partners',
        'Configuration/TSconfig/PartnershipsTeaser/page.tsconfig',
        'Academic Partners: Partnerships Teaser',
    );

    ExtensionManagementUtility::registerPageTSConfigFile(
        'academic_partners',
        'Configuration/TSconfig/Full/page.tsconfig',
        'Academic Partners: All components',
    );
})();
