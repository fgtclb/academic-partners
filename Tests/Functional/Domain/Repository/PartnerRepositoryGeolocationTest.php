<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * The two geocoding queries of `PartnerRepository`.
 *
 * `findNextForGeolocation()` picks the single record `Command\GeocodeCommand` geocodes per
 * run. The command is scheduled, unattended and has no output on the happy path, so a
 * regression in this query does not fail anything - partner addresses simply stop being
 * resolved and the map stays empty. It is the one query here that nothing else exercises.
 *
 * `findGeoLocated()` is the counterpart the map rendering reads, and the pair has to agree:
 * every status the command writes must be picked up by exactly one of them, or a record
 * either gets geocoded forever or never shows up on the map.
 */
final class PartnerRepositoryGeolocationTest extends AbstractAcademicPartnersTestCase
{
    /**
     * `open` is the TCA default of `geocode_status`, so a freshly created partner page is
     * what the command is meant to find.
     */
    #[Test]
    public function theNextPartnerForGeolocationIsTheOneStillMarkedOpen(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $partner = $this->subject()->findNextForGeolocation();

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertSame(10, $partner->getUid());
        $this->assertSame('open', $partner->getGeocodeStatus());
    }

    /**
     * `GeocodeCommand` treats `null` as "nothing to do" and exits successfully, which is the
     * normal end state of a fully geocoded installation. A query that answered with a record
     * here would make the command call the Nominatim API on every schedule run for nothing.
     *
     * The fixture also holds a `failed` record: the command never retries one, so a partner
     * whose address could not be resolved stays out of the queue until an editor touches it.
     */
    #[Test]
    public function thereIsNoNextPartnerWhenNothingIsMarkedOpen(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/nothingLeftToGeocode.csv');

        $this->assertNull($this->subject()->findNextForGeolocation());
    }

    /**
     * The `nothingLeftToGeocode` fixture marks an ordinary page and the root page `open`.
     * Without the doktype constraint the command would try to geocode the address fields of
     * a page that has none and would mark it `failed`, writing to records it does not own.
     */
    #[Test]
    public function aPageThatIsNotAPartnerPageIsNeverGeocoded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/nothingLeftToGeocode.csv');

        $this->assertNull($this->subject()->findNextForGeolocation());
    }

    /**
     * Hidden and deleted partners are excluded by the enable field restrictions rather than
     * by anything the method spells out, and both are `open` in the fixture. The hidden one
     * is the interesting half: it is a record an editor still works on, and it will not be
     * geocoded until it is published - a delay that is invisible in the backend.
     */
    #[Test]
    public function hiddenAndDeletedPartnersAreNeverGeocoded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/nothingLeftToGeocode.csv');

        $this->assertNull($this->subject()->findNextForGeolocation());
    }

    /**
     * A partner whose `geocode_status` is the empty string - the database default of the
     * column, which every record created before the field existed carries - matches neither
     * `open` nor the located statuses. It is invisible to the command and to the map alike,
     * and only an editor saving the record once repairs it.
     */
    #[Test]
    public function aPartnerWithoutAGeocodeStatusIsNeitherGeocodedNorLocated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $this->assertNotSame(14, $this->subject()->findNextForGeolocation()?->getUid());
        $this->assertNotContains(14, $this->resultUids($this->subject()->findGeoLocated()));
    }

    /**
     * The query limits to one row, and without an ordering the DBMS picked it - which of
     * several open partners got geocoded next was arbitrary (ACE-491). Oldest first makes
     * the queue deterministic on every DBMS and keeps a freshly created partner from
     * starving the ones that wait longer. SQLite cannot make this assertion fail because
     * uid order is its natural order; it pins the contract for the DBMS where the pick
     * was arbitrary before.
     */
    #[Test]
    public function withSeveralOpenPartnersTheOldestOneIsGeocodedFirst(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/severalOpenPartners.csv');

        $partner = $this->subject()->findNextForGeolocation();

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertSame(10, $partner->getUid());
    }

    /**
     * Like `findAll()`, the map source orders by the backend `sorting` the editor
     * arranged, and the fixture contradicts creation order on purpose: partner 12
     * (`sorting` 64) precedes partner 11 (`sorting` 128). Without the explicit ordering
     * the marker order belonged to the DBMS (ACE-491).
     */
    #[Test]
    public function geoLocatedPartnersAreReturnedInTheirBackendSortingOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $uids = [];
        foreach ($this->subject()->findGeoLocated() as $partner) {
            $uids[] = (int)$partner->getUid();
        }

        $this->assertSame([12, 11], $uids);
    }

    /**
     * `successful` is written by the command, `manually` by an editor who entered the
     * coordinates by hand. Both mean "has coordinates", and dropping either from the list
     * would empty half the map without any error.
     */
    #[Test]
    public function geoLocatedPartnersAreTheAutomaticallyAndTheManuallyResolvedOnes(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $this->assertSame([11, 12], $this->resultUids($this->subject()->findGeoLocated()));
    }

    /**
     * The complement of the list above: a partner waiting for geocoding and one whose
     * geocoding failed have no usable coordinates, so they must not reach the map.
     */
    #[Test]
    public function openAndFailedPartnersAreNotGeoLocated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $uids = $this->resultUids($this->subject()->findGeoLocated());

        $this->assertNotContains(10, $uids);
        $this->assertNotContains(13, $uids);
    }

    #[Test]
    public function hiddenAndDeletedPartnersAreNotGeoLocated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $uids = $this->resultUids($this->subject()->findGeoLocated());

        $this->assertNotContains(16, $uids);
        $this->assertNotContains(17, $uids);
    }

    /**
     * The `partners` fixture marks an ordinary page `successful`. It carries neither an
     * address nor coordinates, and putting it on the map would place a marker at 0/0.
     */
    #[Test]
    public function aPageThatIsNotAPartnerPageIsNeverGeoLocated(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/partners.csv');

        $this->assertNotContains(2, $this->resultUids($this->subject()->findGeoLocated()));
    }

    /**
     * An installation whose partners are all still waiting hands the map an empty result
     * rather than nothing at all - `findGeoLocated()` is typed non nullable and the map
     * template iterates it directly.
     */
    #[Test]
    public function withoutAnyLocatedPartnerAnEmptyResultIsReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryGeolocation/severalOpenPartners.csv');

        $this->assertCount(0, $this->subject()->findGeoLocated());
        $this->assertSame([], $this->resultUids($this->subject()->findGeoLocated()));
    }

    private function subject(): PartnerRepository
    {
        return $this->get(PartnerRepository::class);
    }

    /**
     * @param QueryResult<Partner> $result
     * @return int[]
     */
    private function resultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $partner) {
            $uids[] = (int)$partner->getUid();
        }
        sort($uids);
        return $uids;
    }
}
