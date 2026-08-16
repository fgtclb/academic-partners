<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * `PartnerRepository::findAll()` overrides the Extbase method of the same name and narrows
 * it to pages of the partner page type. It is not a list-view method: its only caller is
 * `Backend\FormEngine\PartnerItems::itemsProcFunc()`, which turns the result into the select
 * items of the `partner` field of a partnership record.
 *
 * That makes two things load bearing which no frontend test would notice. The doktype
 * constraint keeps every ordinary page out of a select box that would otherwise list the
 * whole page tree, and `initializeObject()` lifting the storage page is what lets the
 * FormEngine see partners at all - a `itemsProcFunc` runs without any storage pid.
 */
final class PartnerRepositoryFindAllTest extends AbstractAcademicPartnersTestCase
{
    /**
     * Pages 1 and 2 are ordinary pages and page 3 is a folder; only the partner page type
     * qualifies. Without the constraint the partner select of a partnership record would
     * offer every page of the installation.
     */
    #[Test]
    public function onlyPagesOfThePartnerPageTypeAreReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/partners.csv');

        $this->assertSame([10, 11], $this->resultUids($this->subject()->findAll()));
    }

    /**
     * Partner 11 lives in a folder while partner 10 sits directly below the root, and both
     * are returned. `initializeObject()` is the only place that lifts the storage page for
     * this method, and its effect is invisible in a test that keeps all records on one pid.
     */
    #[Test]
    public function partnersAreReturnedRegardlessOfTheStoragePageTheyLiveOn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/partners.csv');

        $pids = [];
        foreach ($this->subject()->findAll() ?? [] as $partner) {
            $pids[] = (int)$partner->getPid();
        }
        sort($pids);

        $this->assertSame([1, 3], $pids);
    }

    #[Test]
    public function hiddenPartnersAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/partners.csv');

        $this->assertNotContains(12, $this->resultUids($this->subject()->findAll()));
    }

    #[Test]
    public function deletedPartnersAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/partners.csv');

        $this->assertNotContains(13, $this->resultUids($this->subject()->findAll()));
    }

    #[Test]
    public function anInstallationWithoutPartnerPagesYieldsAnEmptyResult(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/noPartners.csv');

        $this->assertSame([], $this->resultUids($this->subject()->findAll()));
    }

    /**
     * The signature says `?QueryResult` and `PartnerItems::itemsProcFunc()` guards against
     * `null` accordingly, but the method returns `$query->execute()`, which is a result
     * object even when it is empty. Pinning that down keeps the guard from being mistaken
     * for a reachable branch, and would report a change of the contract.
     */
    #[Test]
    public function anEmptyResultIsAQueryResultAndNotNull(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRepositoryFindAll/noPartners.csv');

        $result = $this->subject()->findAll();

        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertCount(0, $result);
    }

    private function subject(): PartnerRepository
    {
        return $this->get(PartnerRepository::class);
    }

    /**
     * @param QueryResult<Partner>|null $result
     * @return int[]
     */
    private function resultUids(?QueryResult $result): array
    {
        $uids = [];
        foreach ($result ?? [] as $partner) {
            $uids[] = (int)$partner->getUid();
        }
        sort($uids);
        return $uids;
    }
}
