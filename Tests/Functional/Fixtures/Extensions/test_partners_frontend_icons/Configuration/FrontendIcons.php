<?php

declare(strict_types=1);

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

// The way a site package replaces the icon of a shipped category type for the frontend.
return [
    'category_types.partners.region' => [
        'provider' => CurrentColorSvgIconProvider::class,
        'source' => 'EXT:test_partners_frontend_icons/Resources/Public/Icons/SiteReplaced.svg',
    ],
];
