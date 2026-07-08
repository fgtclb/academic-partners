<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

final class PartnerRepositoryShowHiddenRecordsTest extends AbstractAcademicPartnersTestCase
{
    private function getPartnerRepository(): PartnerRepository
    {
        return $this->get(PartnerRepository::class);
    }

    private function createDemand(bool $showHiddenRecords): PartnerDemand
    {
        $demand = new PartnerDemand();
        $demand->setShowHiddenRecords($showHiddenRecords);
        return $demand;
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

    #[Test]
    public function findByDemandExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryShowHidden/partners.csv');
        $result = $this->getPartnerRepository()->findByDemand($this->createDemand(false));
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByDemandIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryShowHidden/partners.csv');
        $result = $this->getPartnerRepository()->findByDemand($this->createDemand(true));
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }
}
