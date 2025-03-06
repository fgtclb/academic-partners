<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Partners',
    'description' => 'Extension for showing academic partners in list and map view',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'backend' => '11.5.0-12.4.99',
            'extbase' => '11.5.0-12.4.99',
            'fluid' => '11.5.0-12.4.99',
            'category_types' => '1.0.0-1.9.99',
        ],
        'suggest' => [
            'page_backend_layout' => '1.0.0-1.9.99',
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
