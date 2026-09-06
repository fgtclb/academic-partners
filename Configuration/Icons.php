<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

/*
 * Every identifier here ends up on a record: `academic-partners` is the icon of the
 * academic partner page type (and of the four content elements), the other two are
 * the record icons of the tables this extension ships. All three are registered with
 * the provider of EXT:academic_base, which inlines the file in both markups instead
 * of rendering an <img>. An <img> is opaque to CSS and keeps the colours of its file,
 * so an icon drawn in a dark ink stays dark on the dark cards of the backend colour
 * scheme. Inlined and drawn in `currentColor` it follows the text colour.
 */
return [
    'academic-partners' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academicpartners_domain_model_partnership' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Partnership.svg',
    ],
    'tx_academicpartners_domain_model_role' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Role.svg',
    ],
];
