<?php

declare(strict_types=1);

/**
 * Import map of the frontend modules of this extension.
 *
 * The compiled modules below "Resources/Public/JavaScript/frontend/" are ES
 * modules and are addressed by the bare specifier below, never by a path — only
 * a specifier resolved through the import map receives TYPO3's "?bust=" cache
 * key. Only the "frontend/" prefix is mapped, so a backend module added later
 * cannot be reached from a frontend page.
 *
 * The "core" dependency makes the modules EXT:core declares resolvable here.
 *
 * "leaflet" and "leaflet.markercluster" are the map libraries, built from their
 * npm packages by the asset build (Build/vendor.mjs). The version in the path is
 * the one pinned in Build/package.json and has to follow it.
 */
return [
    'dependencies' => [
        'core',
    ],
    'imports' => [
        '@fgtclb/academic-partners/frontend/' => 'EXT:academic_partners/Resources/Public/JavaScript/frontend/',
        'leaflet' => 'EXT:academic_partners/Resources/Public/JavaScript/vendor/leaflet/1.9.4/leaflet.js',
        'leaflet.markercluster' => 'EXT:academic_partners/Resources/Public/JavaScript/vendor/leaflet.markercluster/1.5.3/leaflet.markercluster.js',
    ],
];
