<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Partners HTTP stub',
    'description' => 'Extension stubbing outgoing HTTP requests for tests',
    'version' => '3.0.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'academic_partners' => '3.0.0',
        ],
    ],
];
