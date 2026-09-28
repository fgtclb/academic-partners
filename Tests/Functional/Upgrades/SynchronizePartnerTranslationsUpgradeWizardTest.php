<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Upgrades;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\AcademicPartners\Upgrades\SynchronizePartnerTranslationsUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * The fixture holds one case per decision the wizard makes: a translation that is out of
 * step, one that already agrees, a hidden pair, a deleted translation, a workspace
 * version and a page that is not a partner at all. Uids 117 to 121 are the cases of the
 * two groups the wizard synchronizes, the coordinates and "Show on map", each on its own:
 * out of step in one group, in both, and detached in one group while the other is still
 * out of step.
 */
final class SynchronizePartnerTranslationsUpgradeWizardTest extends AbstractAcademicPartnersTestCase
{
    #[Test]
    public function noUpdateIsNecessaryWithoutRecords(): void
    {
        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function anOutOfStepTranslationMakesTheUpdateNecessary(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function theTranslationReceivesTheCoordinatesOfItsDefaultRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(['48.137154', '11.576124'], $this->coordinatesOf(110));
    }

    /**
     * A hidden partner is still a partner: it is only hidden, and an editor who unhides
     * it should not have to discover that its translation lost the coordinates.
     */
    #[Test]
    public function aHiddenTranslationIsSynchronizedAsWell(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(['53.551086', '9.993682'], $this->coordinatesOf(112));
    }

    #[Test]
    public function aDeletedTranslationIsLeftAlone(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame([null, null], $this->coordinatesOf(113));
    }

    /**
     * A workspace version is a draft of a record that is synchronized from now on
     * anyway. Rewriting one would change a draft the editor did not touch.
     */
    #[Test]
    public function aWorkspaceVersionIsLeftAlone(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame([null, null], $this->coordinatesOf(114));
    }

    #[Test]
    public function aPageThatIsNotAPartnerIsLeftAlone(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame([null, null], $this->coordinatesOf(120));
    }

    #[Test]
    public function nothingIsLeftToDoAfterTheUpdateRan(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertFalse($this->subject()->updateNecessary());
    }

    /**
     * The fixture's uid 111 already carries its parent's coordinates. It must come out
     * unchanged - and it must not make `updateNecessary()` true on its own, which
     * `nothingIsLeftToDoAfterTheUpdateRan()` covers from the other side.
     */
    #[Test]
    public function aTranslationThatAlreadyAgreesIsUntouched(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(['52.520008', '13.404954'], $this->coordinatesOf(111));
    }

    /**
     * A translation whose default record is deleted has nothing to inherit: copying the
     * deleted row's coordinates would resurrect data the editor removed.
     */
    #[Test]
    public function aTranslationOfADeletedPartnerIsLeftAlone(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame([null, null], $this->coordinatesOf(115));
    }

    /**
     * An editor can detach a synchronized field from its default record on purpose,
     * which writes `custom` into `l10n_state`. Run late - after the upgrade, after
     * people have worked - the wizard would otherwise silently revert that decision.
     */
    #[Test]
    public function aDeliberatelyDetachedCoordinateIsNotReverted(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(['1.111111', '2.222222'], $this->coordinatesOf(116));
    }

    #[Test]
    public function aTranslationOutOfStepInTheSwitchOnlyTakesTheSwitch(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(1, $this->showOnMapOf(117));
        $this->assertSame(['50.0', '8.0'], $this->coordinatesOf(117));
    }

    #[Test]
    public function aTranslationOutOfStepInBothGroupsTakesBoth(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(['51.0', '7.0'], $this->coordinatesOf(118));
        $this->assertSame(1, $this->showOnMapOf(118));
    }

    /**
     * One detached coordinate keeps the pair: half a pair is not a place. The switch is
     * still synchronized.
     */
    #[Test]
    public function detachedCoordinatesKeepTheirValuesWhileTheSwitchIsSynchronized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(['1.5', '2.5'], $this->coordinatesOf(119));
        $this->assertSame(1, $this->showOnMapOf(119));
    }

    #[Test]
    public function aDetachedSwitchKeepsItsValueWhileTheCoordinatesAreSynchronized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SynchronizePartnerTranslations/partners.csv');

        $this->subject()->executeUpdate();

        $this->assertSame(0, $this->showOnMapOf(121));
        $this->assertSame(['53.0', '5.0'], $this->coordinatesOf(121));
    }

    private function showOnMapOf(int $uid): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('show_on_map')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }

    private function subject(): SynchronizePartnerTranslationsUpgradeWizard
    {
        $subject = $this->get(SynchronizePartnerTranslationsUpgradeWizard::class);
        $this->assertInstanceOf(SynchronizePartnerTranslationsUpgradeWizard::class, $subject);

        return $subject;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function coordinatesOf(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('geocode_latitude', 'geocode_longitude')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return [$row['geocode_latitude'] ?? null, $row['geocode_longitude'] ?? null];
    }
}
