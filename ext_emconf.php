<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Partners',
    'description' => 'Extension for showing academic partners in list and map view',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.1.5',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'category_types' => '1.1.5',
        ],
        'suggest' => [
            'page_backend_layout' => '1.0.0-2.9.99',
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
