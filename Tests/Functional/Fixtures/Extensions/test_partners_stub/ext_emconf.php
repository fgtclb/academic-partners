<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TESTS: Academic Partners HTTP stub',
    'description' => 'Extension stubbing outgoing HTTP requests for tests',
    'version' => '2.4.0',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Stefan Bürk',
    'author_email' => 'hello@fgtclb.com',
    'author_company' => 'FGTCLB GmbH',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.22-13.4.99',
            'academic_partners' => '2.4.0',
        ],
    ],
];
