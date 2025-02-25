<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Domain\Model\Partner;

return [
    Partner::class => [
        'tableName' => 'pages',
        'properties' => [
            'doktype' => [
                'fieldName' => 'doktype',
            ],
            'lastUpdated' => [
                'fieldName' => 'lastUpdated',
            ],
            'media' => [
                'fieldName' => 'media',
            ],
        ],
    ],
];
