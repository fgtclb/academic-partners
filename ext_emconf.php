<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FGTCLB: Academic Partners',
    'description' => 'Extension for showing academic partners in list and map view',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'FGTCLB',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'backend' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'academic_base' => '3.0.0',
            'category_types' => '3.0.0',
        ],
        'suggests' => [
            'page_backend_layout' => '2.0.0-2.9.99',
        ],
    ],
];
