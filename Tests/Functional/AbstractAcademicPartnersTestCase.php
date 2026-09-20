<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional;

use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

abstract class AbstractAcademicPartnersTestCase extends FunctionalTestCase
{
    /**
     * The extension ships an upgrade wizard, so it depends on EXT:install - and a
     * declared dependency has to be loadable for every functional test, not only the
     * ones that touch the wizard. The sibling extensions that ship wizards declare it
     * on their base test case for the same reason.
     */
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/category-types',
        'fgtclb/academic-partners',
    ];
}
