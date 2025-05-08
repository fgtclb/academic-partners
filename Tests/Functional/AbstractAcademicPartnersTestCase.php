<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicPartnersTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/category-types',
        'fgtclb/academic-partners',
    ];
}
