<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * `PartnershipRepository::findByPid()` is the only method of the class and the only way
 * partnerships reach the frontend: `PartnerController::partnershipsListAction()` and
 * `partnershipsTeaserAction()` call it with the pid of the content element, and
 * `PartnershipProcessor` calls it with the uid of the current page record.
 *
 * Its argument is therefore *not* a storage pid at all - the method matches the group
 * field `page`, which points at the page a partnership is attached to, while the record
 * itself may live anywhere. The three query settings the method changes (storage page,
 * sys language, language aspect) all exist to make that possible, and each of them can be
 * dropped without any caller noticing until content goes missing in production.
 */
final class PartnershipRepositoryFindByPidTest extends AbstractAcademicPartnersTestCase
{
    /**
     * The `page` of the partnership decides, not its `pid`: records 1 and 2 point at page 2
     * while living on two different storage pages, record 3 lives next to record 1 but
     * points elsewhere.
     */
    #[Test]
    public function onlyThePartnershipsAttachedToTheRequestedPageAreReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertSame([1, 2], $this->resultUids($this->subject()->findByPid(2)));
    }

    /**
     * `setRespectStoragePage(false)` is what allows an editor to keep partnership records in
     * a storage folder while the plugin sits on another page. Without it the result would be
     * limited to the storage pids of the plugin, which no caller configures - the frontend
     * would silently render nothing.
     */
    #[Test]
    public function partnershipsAreReturnedRegardlessOfTheStoragePageTheyLiveOn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $pids = [];
        foreach ($this->subject()->findByPid(2) as $partnership) {
            $pids[] = (int)$partnership->getPid();
        }
        sort($pids);

        $this->assertSame([2, 4], $pids);
    }

    #[Test]
    public function hiddenPartnershipsAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertNotContains(4, $this->resultUids($this->subject()->findByPid(2)));
    }

    #[Test]
    public function deletedPartnershipsAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertNotContains(5, $this->resultUids($this->subject()->findByPid(2)));
    }

    /**
     * A page that exists but carries no partnership is the ordinary case for every page the
     * `PartnershipProcessor` runs on, so it has to answer with an empty result rather than
     * with everything.
     */
    #[Test]
    public function aPageWithoutPartnershipsYieldsAnEmptyResult(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertSame([], $this->resultUids($this->subject()->findByPid(12)));
    }

    #[Test]
    public function anUnknownPageYieldsAnEmptyResult(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertSame([], $this->resultUids($this->subject()->findByPid(999)));
    }

    /**
     * Page `0` is not a neutral "nothing" value here, and both frontend callers can reach it:
     * they pass `(int)($contentElementData['pid'] ?? 0)`. The TCA `page` column defaults to
     * `0` as well, so a partnership saved without a page assignment - the column is a group
     * field with `minitems` 1 but a `0` default - becomes visible to exactly that fallback.
     */
    #[Test]
    public function pageZeroReturnsThePartnershipsThatHaveNoPageAssigned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertSame([6], $this->resultUids($this->subject()->findByPid(0)));
    }

    /**
     * `PartnershipProcessor` and both controller actions build the role list from
     * `getRole()`, and the templates render `getPartner()`. Both relations are resolved by
     * the data mapper rather than by the repository, so a mapping change is invisible to
     * every other test in this class.
     */
    #[Test]
    public function returnedPartnershipsCarryTheirPartnerAndRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $partnership = null;
        foreach ($this->subject()->findByPid(2) as $candidate) {
            if ((int)$candidate->getUid() === 1) {
                $partnership = $candidate;
            }
        }

        $this->assertInstanceOf(Partnership::class, $partnership);
        $this->assertSame(2, $partnership->getPage());

        $partner = $partnership->getPartner();
        $role = $partnership->getRole();

        $this->assertNotNull($partner);
        $this->assertSame('Alpha University', $partner->getTitle());
        $this->assertNotNull($role);
        $this->assertSame('Research partner', $role->getName());
        $this->assertSame('Research partner - Alpha University', $partnership->getLabel());
    }

    /**
     * The method combines `setRespectSysLanguage(false)` with an explicit `OVERLAYS_ON`
     * language aspect. Dropping the language constraint means the translated row matches the
     * `page` comparison too, so a translated partnership is returned *in addition to* its
     * default language record: two result objects for one editorial record. This is
     * characterisation - it is what the frontend renders today, and it is the reason the two
     * settings must not be changed one at a time.
     */
    #[Test]
    public function aTranslatedPartnershipIsReturnedNextToItsDefaultLanguageRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/translatedPartnerships.csv');

        $result = $this->subject()->findByPid(2);

        $localizedUids = [];
        foreach ($result as $partnership) {
            $localizedUids[] = (int)$partnership->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
        }
        sort($localizedUids);

        $this->assertCount(2, $result);
        $this->assertSame([1, 1], $this->resultUids($result));
        $this->assertSame([1, 2], $localizedUids);
    }

    /**
     * The second half of the same behaviour, and the part that makes it a defect rather than
     * a curiosity: the extra object is a translation only in the sense that it was mapped
     * from the translated row. Its role relation is resolved in the default language, so both
     * objects carry the same role name and the translated role record is never used - a
     * frontend that renders a translated partnership list shows the default language role
     * twice, once per duplicate.
     */
    #[Test]
    public function theTranslatedPartnershipStillCarriesTheDefaultLanguageRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/translatedPartnerships.csv');

        $roleNames = [];
        foreach ($this->subject()->findByPid(2) as $partnership) {
            $roleNames[] = $partnership->getRole()?->getName();
        }

        $this->assertSame(['Research partner', 'Research partner'], $roleNames);
    }

    private function subject(): PartnershipRepository
    {
        return $this->get(PartnershipRepository::class);
    }

    /**
     * @param QueryResult<Partnership> $result
     * @return int[]
     */
    private function resultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $partnership) {
            $uids[] = (int)$partnership->getUid();
        }
        sort($uids);
        return $uids;
    }
}
