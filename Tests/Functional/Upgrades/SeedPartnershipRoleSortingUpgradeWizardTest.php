<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Upgrades;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\AcademicPartners\Upgrades\SeedPartnershipRoleSortingUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The fixture holds partnerships of two roles plus one partnership without a role,
 * and its `sorting` values contradict uid order in both roles, so the expected
 * result differs from every order the database could return by accident.
 *
 * Role 1 holds uid 3 (`sorting` 1), uids 1 and 2 (both `sorting` 2, settled by uid)
 * and the deleted uid 7 (`sorting` 3); role 2 holds uid 5 (`sorting` 5) before
 * uid 4 (`sorting` 7). Uid 6 carries no role and has to stay at 0.
 */
final class SeedPartnershipRoleSortingUpgradeWizardTest extends AbstractAcademicPartnersTestCase
{
    private const TABLE = 'tx_academicpartners_domain_model_partnership';
    private const FIXTURE = __DIR__ . '/Fixtures/SeedPartnershipRoleSorting/partnershipsOfTwoRoles.csv';

    #[Test]
    public function updateIsNecessaryWhileAPartnershipOfARoleIsUnseeded(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateNumbersThePartnershipsOfEveryRole(): void
    {
        $this->importCSVDataSet(self::FIXTURE);

        $this->assertTrue($this->getSubject()->executeUpdate());

        $this->assertSame(
            [1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 0, 7 => 4],
            $this->fetchRoleSortingByUid(),
        );
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();

        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * Running it twice writes the same numbers: the wizard derives them from
     * `sorting`, which it does not touch.
     */
    #[Test]
    public function executeUpdateIsIdempotent(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $afterFirstRun = $this->fetchRoleSortingByUid();

        $this->getSubject()->executeUpdate();

        $this->assertSame($afterFirstRun, $this->fetchRoleSortingByUid());
    }

    /**
     * An installation that has no partnership with a role at all is not offered the
     * wizard.
     */
    #[Test]
    public function updateIsNotNecessaryWithoutPartnershipsCarryingARole(): void
    {
        $this->assertFalse($this->getSubject()->updateNecessary());
    }

    /**
     * A second run **appends**, it does not renumber: the ranks the first run wrote -
     * and any arrangement an editor made on top of them - are left alone, and only a
     * partnership that has none is given one, behind them.
     */
    #[Test]
    public function executeUpdateAppendsWithoutResettingWhatIsAlreadyRanked(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->getSubject()->executeUpdate();
        $afterFirstRun = $this->fetchRoleSortingByUid();
        // The one partnership without a role joins it, the way the DataHandler hook
        // would not have to: written straight to the database, so nothing ranks it.
        $this->assignParentWithoutRank(6, 1);

        $this->getSubject()->executeUpdate();

        $this->assertSame([1 => 2, 2 => 3, 3 => 1, 4 => 2, 5 => 1, 6 => 5, 7 => 4], $this->fetchRoleSortingByUid());
        unset($afterFirstRun[6]);
        $this->assertSame(
            $afterFirstRun,
            array_diff_key($this->fetchRoleSortingByUid(), [6 => true]),
            'The second run changed a rank it had already written.',
        );
    }

    /**
     * Writes the parent column straight to the database, without the DataHandler, so
     * the row ends up in the state an installation is in before the wizard has run.
     */
    private function assignParentWithoutRank(int $uid, int $parentUid): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $connection->update(self::TABLE, ['role' => $parentUid, 'role_sorting' => 0], ['uid' => $uid]);
    }

    private function getSubject(): SeedPartnershipRoleSortingUpgradeWizard
    {
        $subject = $this->get(SeedPartnershipRoleSortingUpgradeWizard::class);
        $this->assertInstanceOf(SeedPartnershipRoleSortingUpgradeWizard::class, $subject);
        return $subject;
    }

    /**
     * @return array<int, int>
     */
    private function fetchRoleSortingByUid(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'role_sorting')
            ->from(self::TABLE)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $sortingByUid = [];
        foreach ($rows as $row) {
            $sortingByUid[(int)$row['uid']] = (int)$row['role_sorting'];
        }
        return $sortingByUid;
    }
}
