<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\DeprecatedCoreLabelsTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see DeprecatedCoreLabelsTrait
 */
final class DeprecatedCoreLabelsTest extends AbstractAcademicPartnersTestCase
{
    use DeprecatedCoreLabelsTrait;

    #[Group('not-core-13')]
    #[Test]
    public function tcaDoesNotReferenceCoreLabelsRetiredInV14(): void
    {
        $this->assertTcaHasNoDeprecatedCoreLabelReferences(['tx_academicpartners_']);
    }
}
