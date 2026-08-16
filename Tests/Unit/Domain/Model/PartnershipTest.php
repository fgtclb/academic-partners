<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use FGTCLB\AcademicPartners\Domain\Model\Role;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `getLabel()` is the only method of `Partnership` that decides anything. It is what
 * `Backend\FormEngine\PartnershipLabels::getTitle()` writes into the record title of an
 * inline element, so an editor picks a partnership out of a list by exactly this string.
 * Both relations are nullable in TCA, so all four combinations occur in a real record set.
 */
final class PartnershipTest extends UnitTestCase
{
    #[Test]
    public function theLabelJoinsTheRoleAndThePartner(): void
    {
        $subject = new Partnership();
        $subject->_setProperty('role', $this->role('Sponsor'));
        $subject->_setProperty('partner', $this->partner('Example University'));

        $this->assertSame('Sponsor - Example University', $subject->getLabel());
    }

    /**
     * The role comes first. Reversing the two would silently re-sort every inline label in
     * the backend, which is a visible regression and nothing a type check catches.
     */
    #[Test]
    public function theRoleIsNamedBeforeThePartner(): void
    {
        $subject = new Partnership();
        $subject->_setProperty('role', $this->role('First'));
        $subject->_setProperty('partner', $this->partner('Second'));

        $this->assertStringStartsWith('First', $subject->getLabel());
    }

    /**
     * A partnership without a role is a legal record. The separator has to disappear with
     * the missing part rather than leaving a dangling " - " in the record title.
     */
    #[Test]
    public function aMissingRoleLeavesNoSeparator(): void
    {
        $subject = new Partnership();
        $subject->_setProperty('partner', $this->partner('Example University'));

        $this->assertNull($subject->getRole());
        $this->assertSame('Example University', $subject->getLabel());
    }

    /**
     * The mirrored case: a role that has not been pointed at a partner yet, which is what
     * a freshly created inline record looks like before it is saved.
     */
    #[Test]
    public function aMissingPartnerLeavesNoSeparator(): void
    {
        $subject = new Partnership();
        $subject->_setProperty('role', $this->role('Sponsor'));

        $this->assertNull($subject->getPartner());
        $this->assertSame('Sponsor', $subject->getLabel());
    }

    /**
     * An empty label is what `PartnershipLabels` hands FormEngine for an untouched new
     * record, and FormEngine falls back to its own "[No title]" for that. Returning
     * something like " - " instead would suppress that fallback.
     */
    #[Test]
    public function anUnrelatedPartnershipHasNoLabel(): void
    {
        $subject = new Partnership();

        $this->assertSame('', $subject->getLabel());
    }

    /**
     * Both relations set but unnamed. The parts are joined without being checked for
     * content, so the label is the bare separator - the one case where the record title
     * ends up as visual noise instead of an empty string. Pinned deliberately: it is
     * current behaviour, and a change to it should be a decision, not a side effect.
     */
    #[Test]
    public function relationsWithoutATitleStillProduceTheSeparator(): void
    {
        $subject = new Partnership();
        $subject->_setProperty('role', new Role());
        $subject->_setProperty('partner', new Partner());

        $this->assertSame(' - ', $subject->getLabel());
    }

    private function role(string $name): Role
    {
        $role = new Role();
        $role->_setProperty('name', $name);
        return $role;
    }

    private function partner(string $title): Partner
    {
        $partner = new Partner();
        $partner->_setProperty('title', $title);
        return $partner;
    }
}
