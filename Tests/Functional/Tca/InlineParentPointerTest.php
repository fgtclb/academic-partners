<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\InlineParentPointerAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The inline children of this extension keep their parent when it is edited in a
 * workspace and published (ACE-907), see {@see InlineParentPointerAssertionTrait}.
 */
final class InlineParentPointerTest extends AbstractAcademicPartnersTestCase
{
    use InlineParentPointerAssertionTrait;

    #[Test]
    public function inlineChildrenKeepTheLiveParent(): void
    {
        $this->assertInlineParentPointersKeepTheLiveParent(['tx_academicpartners_'], [
            'pages.tx_academicpartners_partnerships -> tx_academicpartners_domain_model_partnership.page',
            'tx_academicpartners_domain_model_role.partnerships -> tx_academicpartners_domain_model_partnership.role',
        ]);
    }
}
