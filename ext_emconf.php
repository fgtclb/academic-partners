<?php

$EM_CONF[$_EXTKEY] = [
    'author' => 'FGTCLB',
    'author_company' => 'FGTCLB GmbH',
    'author_email' => 'hello@fgtclb.com',
    'category' => 'fe,be',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'backend' => '12.4.22-13.4.99',
            'extbase' => '12.4.22-13.4.99',
            'fluid' => '12.4.22-13.4.99',
            'academic_base' => '2.0.2',
            'category_types' => '2.0.2',
        ],
        'suggest' => [
            'page_backend_layout' => '2.0.0-2.9.99',
        ],
    ],
    'description' => 'Extension for showing academic partners in list and map view',
    'state' => 'beta',
    'title' => 'FGTCLB: Academic Partners',
    'version' => '2.0.2',
];
