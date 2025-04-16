<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'academic-partners' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
    ],
    'tx_academicpartners_domain_model_partnership' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Partnership.svg',
    ],
    'tx_academicpartners_domain_model_role' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:academic_partners/Resources/Public/Icons/Role.svg',
    ],
];
