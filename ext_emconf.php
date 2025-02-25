<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Partners',
    'description' => 'Extension for showing academic partners in list and map view',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.4.99',
            'page_backend_layout' => '*',
            'category_types' => '*',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'FGTCLB\\AcademicPartners\\' => 'Classes/',
        ],
    ],
    'autoload-dev' => [
        'psr-4' => [
            'FGTCLB\\AcademicPartners\\Tests\\' => 'Tests/',
        ],
    ],
];
