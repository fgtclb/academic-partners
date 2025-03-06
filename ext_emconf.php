<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Partners',
    'description' => 'Extension for showing academic partners in list and map view',
    'category' => 'fe,be',
    'state' => 'beta',
    'version' => '1.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'backend' => '12.4.0-13.4.99',
            'extbase' => '12.4.0-13.4.99',
            'fluid' => '12.4.0-13.4.99',
            'category_types' => '2.0.0-2.9.99',
        ],
        'suggest' => [
            'page_backend_layout' => '2.0.0-2.9.99',
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
