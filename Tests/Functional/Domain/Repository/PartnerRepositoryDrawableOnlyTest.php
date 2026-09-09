<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * `PartnerDemand::setDrawableOnly()` on `findByDemand()` - the query the map actually
 * runs. `findGeoLocated()` answers the same question and is covered next door, but the
 * map does not call it: swapping it in would drop the plugin's filters, its sorting and
 * the `showHiddenRecords` option (ACE-562).
 *
 * The distinction this pins is between a coordinate that is *absent* and one that is
 * *zero*. Absent means `NULL` or the empty string, and half a coordinate is no
 * coordinate. A stored zero is a real place: longitude 0 runs through the United
 * Kingdom, France, Spain and Ghana, and latitude 0 is the equator - only the pair 0/0
 * is open ocean and means "nothing was written". A future simplification to
 * `latitude == 0 || longitude == 0` would pass every plugin test and quietly remove
 * partners that are genuinely there; it fails here.
 */
final class PartnerRepositoryDrawableOnlyTest extends AbstractAcademicPartnersTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryDrawableOnly/partners.csv');
    }

    /**
     * The list action leaves the flag off, and a partner without coordinates is still a
     * perfectly good list entry.
     */
    #[Test]
    public function withoutTheFlagEveryPartnerIsReturned(): void
    {
        $this->assertSame(
            [10, 11, 12, 13, 14, 15, 16],
            $this->sortedResultUids($this->subject()->findByDemand($this->demand(false))),
        );
    }

    #[Test]
    public function onlyPartnersWithUsableCoordinatesAreReturned(): void
    {
        $this->assertSame(
            [10, 14, 15],
            $this->sortedResultUids($this->subject()->findByDemand($this->demand(true))),
        );
    }

    #[Test]
    public function aPartnerOnThePrimeMeridianIsDrawable(): void
    {
        $this->assertContains(14, $this->sortedResultUids($this->subject()->findByDemand($this->demand(true))));
    }

    #[Test]
    public function aPartnerOnTheEquatorIsDrawable(): void
    {
        $this->assertContains(15, $this->sortedResultUids($this->subject()->findByDemand($this->demand(true))));
    }

    #[Test]
    public function aPartnerAtZeroZeroIsNotDrawable(): void
    {
        $this->assertNotContains(13, $this->sortedResultUids($this->subject()->findByDemand($this->demand(true))));
    }

    #[Test]
    public function aPartnerWithOnlyOneCoordinateIsNotDrawable(): void
    {
        $this->assertNotContains(16, $this->sortedResultUids($this->subject()->findByDemand($this->demand(true))));
    }

    private function subject(): PartnerRepository
    {
        return $this->get(PartnerRepository::class);
    }

    private function demand(bool $drawableOnly): PartnerDemand
    {
        $demand = new PartnerDemand();
        $demand->setDrawableOnly($drawableOnly);

        return $demand;
    }

    /**
     * @param QueryResult<Partner> $result
     * @return int[]
     */
    private function sortedResultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $partner) {
            $uids[] = (int)$partner->getUid();
        }
        sort($uids);

        return $uids;
    }
}
