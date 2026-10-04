<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * The backend icons of this extension: Font Awesome Free solid, drawn in `currentColor`
 * and inlined by the provider of EXT:academic_base in both markups, so they take the
 * colour of the surrounding text in both backend colour schemes. An <img> would be
 * opaque to CSS and keep the ink of its file on the dark cards of a dark scheme.
 * Licence and origin of the own files: Resources/Public/Icons/LICENSE-font-awesome.txt.
 * The two record icons use the shared `info` drawings of EXT:academic_base.
 *
 * Identifiers follow `tx-<extension key without underscores>-<group>-<name>`: `doktype`
 * for the academic partner page type, `plugin` for the content elements, `record` for
 * the tables of this extension. The page type and the content element of the partners
 * linked to a page share one drawing but not one identifier, so a project replaces
 * either by registering it again in its own Configuration/Icons.php. The category type and group icons are registered by
 * EXT:category_types from Configuration/CategoryTypes.yaml, in this registry and in the
 * frontend icon registry of EXT:academic_base.
 */
return [
    'tx-academicpartners-doktype-partner' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg',
    ],
    'tx-academicpartners-plugin-partners' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg',
    ],
    'tx-academicpartners-plugin-list' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/list.svg',
    ],
    'tx-academicpartners-plugin-map' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/map.svg',
    ],
    'tx-academicpartners-plugin-partnerships-teaser' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/plugin/partnerships-teaser.svg',
    ],
    'tx-academicpartners-record-partnership' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/partnership.svg',
    ],
    'tx-academicpartners-record-role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_base/Resources/Public/Icons/info/role.svg',
    ],
];
