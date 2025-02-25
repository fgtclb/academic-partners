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
    $doktype = PageTypes::ACADEMIC_PARTNERS;

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
        'abbreviation' => [
            'label' => $ll('columns.abbreviation.label'),
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'description' => [
            'label' => $ll('columns.description.label'),
            'config' => [
                'type' => 'text',
                'rows' => 5,
            ],
        ],
        'link' => [
            'label' => $ll('columns.link.label'),
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'max' => 255,
            ],
        ],
        'address_street' => [
            'label' => $ll('columns.address_street.label'),
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'address_street_number' => [
            'label' => $ll('columns.address_street_number.label'),
            'config' => [
                'type' => 'input',
                'max' => 8,
                'eval' => 'trim',
            ],
        ],
        'address_zip' => [
            'label' => $ll('columns.address_zip.label'),
            'config' => [
                'type' => 'input',
                'max' => 16,
                'eval' => 'trim',
            ],
        ],
        'address_city' => [
            'label' => $ll('columns.address_city.label'),
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'address_country' => [
            'label' => $ll('columns.address_country.label'),
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll('columns.address_country.I.default.label'),
                        '',
                    ],
                ],
                'itemsProcFunc' => FGTCLB\AcademicPartners\Backend\FormEngine\CountryItems::class . '->itemsProcFunc',
            ],
        ],
        'address_additional' => [
            'label' => $ll('columns.address_additional.label'),
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
        'geocode_longitude' => [
            'label' => $ll('columns.geocode_longitude.label'),
            'config' => [
                'type' => 'input',
                'max' => 20,
                'eval' => 'trim',
            ],
        ],
        'geocode_latitude' => [
            'label' => $ll('columns.geocode_latitude.label'),
            'config' => [
                'type' => 'input',
                'max' => 20,
                'eval' => 'trim',
            ],
        ],
        'geocode_last_run' => [
            'label' => $ll('columns.geocode_last_run.label'),
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
            ],
        ],
        'geocode_status' => [
            'label' => $ll('columns.geocode_status.label'),
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll('columns.geocode_status.I.open.label'),
                        'open',
                    ],
                    [
                        $ll('columns.geocode_status.I.successful.label'),
                        'successful',
                    ],
                    [
                        $ll('columns.geocode_status.I.failed.label'),
                        'failed',
                    ],
                    [
                        $ll('columns.geocode_status.I.manually.label'),
                        'manually',
                    ],
                ],
                'default' => 'open',
            ],
        ],
        'geocode_message' => [
            'label' => $ll('columns.geocode_message.label'),
            'config' => [
                'type' => 'input',
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'show_on_map' => [
            'label' => $ll('columns.show_on_map.label'),
            'config' => [
                'type' => 'check',
                'default' => true,
            ],
        ],
    ];

    ArrayUtility::mergeRecursiveWithOverrule(
        $GLOBALS['TCA']['pages']['palettes'],
        [
            'address' => [
                'label' => $ll('palettes.address.label'),
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
                'label' => $ll('palettes.geocode.label'),
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

    ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        implode(',', [
            '--div--;' . $ll('pages.div.partner_information'),
            '--palette--;;address',
            '--palette--;;geocode',
        ]),
        (string)$doktype,
        'after:title'
    );
})();
