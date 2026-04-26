<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\ExtensionsLoadedTestsTrait;

final class ExtensionLoadedTest extends AbstractAcademicPartnersTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        'fgtclb/academic-partners',
        // extension keys
        'academic_base',
        'academic_partners',
    ];
}
