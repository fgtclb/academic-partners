<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

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
     * The fixture holds partnership 1 and its German translation 2, which is attached to
     * the default language page 2 as well, the shape that localizing a role created until
     * ACE-874. In the default language the translation is not returned next to its default
     * record any more (ACE-430): one object, mapped from the default language row.
     */
    #[Test]
    public function aTranslatedPartnershipIsReturnedOnceInTheDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/translatedPartnerships.csv');

        $result = $this->subject()->findByPid(2);

        $localizedUids = [];
        foreach ($result as $partnership) {
            $localizedUids[] = (int)$partnership->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
        }

        $this->assertCount(1, $result);
        $this->assertSame([1], $this->resultUids($result));
        $this->assertSame([1], $localizedUids);
    }

    /**
     * The single object of the default language carries the role of the default language,
     * not a second copy of it next to a translated object (ACE-430).
     */
    #[Test]
    public function theDefaultLanguagePartnershipCarriesTheDefaultLanguageRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/translatedPartnerships.csv');

        $roleNames = [];
        foreach ($this->subject()->findByPid(2) as $partnership) {
            $roleNames[] = $partnership->getRole()?->getName();
        }

        $this->assertSame(['Research partner'], $roleNames);
    }

    /**
     * The language fixture attaches seven partnerships to page 2 and translates the page
     * into German as page 12:
     *
     * - 1, translated as 11 onto page 12, the way translating the partner page does it,
     * - 2, translated as 12, a copy attached to page 2, the way a role localization did
     *   it until ACE-874,
     * - 3, untranslated, sorted last, see
     *   {@see aTranslatedPartnershipAfterAnUntranslatedOneNamesTheDefaultRole()},
     * - 4, a German record without a default language record,
     * - 5, a record for all languages,
     * - 6, hidden, with a visible translation 16 on page 12,
     * - 7, translated as 17 onto page 12, with role 3, which has no German translation.
     *
     * Role 1 is translated into German as role 2, role 3 is not.
     *
     * @param int[] $expectedUids
     * @param int[] $expectedLocalizedUids
     * @param list<string|null> $expectedRoleNames
     */
    private function assertLanguageResult(
        int $languageId,
        array $expectedUids,
        array $expectedLocalizedUids,
        array $expectedRoleNames,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnershipsPerLanguage.csv');
        $this->setUpLanguageAspect($languageId);

        $partnerships = iterator_to_array($this->subject()->findByPid(2));

        $this->assertSame(
            $expectedUids,
            array_map(static fn(Partnership $partnership): int => (int)$partnership->getUid(), $partnerships),
            'Unexpected uids.'
        );
        $this->assertSame(
            $expectedLocalizedUids,
            array_map(
                static fn(Partnership $partnership): int => (int)$partnership->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
                $partnerships
            ),
            'Unexpected localized uids.'
        );
        $this->assertSame(
            $expectedRoleNames,
            array_map(static fn(Partnership $partnership): ?string => $partnership->getRole()?->getName(), $partnerships),
            'Unexpected role names.'
        );
    }

    /**
     * In the default language only the default language records and the record for all
     * languages are returned, each once. Neither the copy 12 nor the German record 4 is.
     */
    #[Test]
    public function theDefaultLanguageReturnsEachPartnershipOnce(): void
    {
        $this->assertLanguageResult(
            0,
            [1, 2, 5, 7, 3],
            [1, 2, 5, 7, 3],
            ['Research partner', 'Research partner', 'Research partner', 'Funding partner', 'Research partner'],
        );
    }

    /**
     * In German every partnership is returned once. 1 and 7 as their translations on the
     * page translation, which the query only reaches through the overlay of their default
     * record. 2 as its copy 12 on page 2, which stands for it and takes its position from
     * its own `sorting` (36). The German record 4 and the record 5 for all languages as
     * themselves. The untranslated 3 as its default record, with the role of the default
     * language. A partnership in German names its role in German where the role has a
     * translation, and in the default language where it has none (7).
     */
    #[Test]
    public function aTranslatedLanguageReturnsEachPartnershipOnce(): void
    {
        $this->assertLanguageResult(
            1,
            [1, 2, 4, 5, 7, 3],
            [11, 12, 4, 5, 17, 3],
            ['Forschungspartner', 'Forschungspartner', 'Forschungspartner', 'Forschungspartner', 'Funding partner', 'Research partner'],
        );
    }

    /**
     * Partnership 2 has two German translations: the copy 12 on page 2, which sorts
     * first, and 13 on the page translation. The partnership is returned once, in the
     * position of the copy, the only one of the two the page matches. Which translation
     * the object is mapped from is not decided here: the overlay of TYPO3 looks the
     * translation up by its default record and language without an order, so SQLite
     * answers 12 and PostgreSQL 13. Both carry the partner and the role of the default
     * record, which are not translated.
     */
    #[Test]
    public function aCopyNextToATranslationIsReturnedOnceInThePositionOfTheCopy(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/copyAndTranslation.csv');
        $this->setUpLanguageAspect(1);

        $partnerships = iterator_to_array($this->subject()->findByPid(2));

        $this->assertSame(
            [2, 1],
            array_map(static fn(Partnership $partnership): int => (int)$partnership->getUid(), $partnerships)
        );
        $this->assertContains(
            (int)$partnerships[0]->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            [12, 13]
        );
        $this->assertSame('Beta Institute', $partnerships[0]->getPartner()?->getTitle());
        $this->assertSame('Forschungspartner', $partnerships[0]->getRole()?->getName());
    }

    /**
     * A language into which no partnership is translated returns the default language
     * records and the record for all languages, and nothing of another language.
     */
    #[Test]
    public function aLanguageWithoutTranslationsReturnsTheDefaultLanguagePartnerships(): void
    {
        $this->assertLanguageResult(
            3,
            [1, 2, 5, 7, 3],
            [1, 2, 5, 7, 3],
            ['Research partner', 'Research partner', 'Research partner', 'Funding partner', 'Research partner'],
        );
    }

    /**
     * The data mapper of TYPO3 v12 and v13 resolves a relation from the identity map by
     * the plain uid of the related record when that key is known (`DataMapper::
     * mapObjectToClassProperty()`). An untranslated partnership loads the default
     * language role under that key, and a translated partnership sorted after it with the
     * same role then names the role of the default language as well. Characterisation of
     * the core, the repository cannot change it.
     */
    #[Test]
    public function aTranslatedPartnershipAfterAnUntranslatedOneNamesTheDefaultRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/untranslatedBeforeTranslated.csv');
        $this->setUpLanguageAspect(1);

        $roleNames = [];
        foreach ($this->subject()->findByPid(2) as $partnership) {
            $roleNames[] = $partnership->getRole()?->getName();
        }

        $this->assertSame(['Research partner', 'Research partner'], $roleNames);
    }

    /**
     * Partnership 1 is visible, its German translation 11 is attached to page 2 as well
     * and hidden. In German the partnership renders as its default record. The pre-query
     * leaves the hidden translation out, so it keeps the default record, which the overlay
     * then passes through, since it does not see a hidden translation either.
     */
    #[Test]
    public function aPartnershipWithAHiddenTranslationIsReturnedAsItsDefaultRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/hiddenTranslation.csv');
        $this->setUpLanguageAspect(1);

        $partnerships = iterator_to_array($this->subject()->findByPid(2));

        $this->assertSame(
            [1],
            array_map(static fn(Partnership $partnership): int => (int)$partnership->getUid(), $partnerships)
        );
        $this->assertSame(
            [1],
            array_map(
                static fn(Partnership $partnership): int => (int)$partnership->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
                $partnerships
            )
        );
    }

    /**
     * A frontend preview of hidden records includes hidden content in the visibility of
     * the context, and the Extbase query then returns hidden records. The pre-query
     * follows it, so the hidden partnership 6 is returned in the preview, in German as
     * its translation 16. Without the preview it is not, see
     * {@see theDefaultLanguageReturnsEachPartnershipOnce()}.
     */
    #[Test]
    public function aPreviewOfHiddenRecordsReturnsTheHiddenPartnership(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnershipsPerLanguage.csv');
        $this->setUpFrontendRequest();
        GeneralUtility::makeInstance(Context::class)->setAspect('visibility', new VisibilityAspect(false, true));

        foreach ([0 => [6, 6], 1 => [6, 16]] as $languageId => [$expectedUid, $expectedLocalizedUid]) {
            $this->setUpLanguageAspect($languageId);
            $hidden = array_values(array_filter(
                iterator_to_array($this->subject()->findByPid(2)),
                static fn(Partnership $partnership): bool => (int)$partnership->getUid() === $expectedUid
            ));
            $this->assertCount(1, $hidden, sprintf('Hidden partnership missing in language %d.', $languageId));
            $this->assertSame(
                $expectedLocalizedUid,
                (int)$hidden[0]->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
                sprintf('Unexpected localized uid in language %d.', $languageId)
            );
        }
    }

    /**
     * Extbase applies the visibility of the context to a query only in a frontend
     * request, and its configuration manager then reads the TypoScript of the request.
     */
    private function setUpFrontendRequest(): void
    {
        // The constructor of TYPO3 v12 takes two arguments, the one of v13 four, and the
        // configuration manager reads the setup only.
        $frontendTypoScript = (new \ReflectionClass(FrontendTypoScript::class))->newInstanceWithoutConstructor();
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    /**
     * `count()` runs its own statement, so it agrees with the iteration only when the query
     * selects one row per partnership, in every language.
     */
    #[Test]
    public function countAgreesWithTheIterationInEveryLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnershipsPerLanguage.csv');

        foreach ([0 => 5, 1 => 6, 3 => 5] as $languageId => $expectedCount) {
            $this->setUpLanguageAspect($languageId);
            $result = $this->subject()->findByPid(2);
            $this->assertSame($expectedCount, $result->count(), sprintf('Unexpected count() in language %d.', $languageId));
            $this->assertCount($expectedCount, iterator_to_array($result), sprintf('Unexpected number of objects in language %d.', $languageId));
        }
    }

    /**
     * The language aspect a frontend request in that language sets, with the overlay type
     * the repository replaces.
     */
    private function setUpLanguageAspect(int $languageId): void
    {
        GeneralUtility::makeInstance(Context::class)->setAspect(
            'language',
            new LanguageAspect($languageId, $languageId, LanguageAspect::OVERLAYS_ON)
        );
    }

    /**
     * The partnership table is manually sortable (TCA ctrl `sortby`), and the fixture
     * deliberately contradicts creation order: record 2 (`sorting` 16) precedes record 1
     * (`sorting` 32). Without the explicit ordering the DBMS decides - uid order on
     * SQLite, arbitrary on PostgreSQL, where on the 3.x line the partnership teaser rendered
     * a different partner on two renders of the same seed data (ACE-491).
     */
    #[Test]
    public function partnershipsAreReturnedInTheirBackendSortingOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnerships.csv');

        $this->assertSame([2, 1], $this->orderedResultUids($this->subject()->findByPid(2)));
    }

    /**
     * Two partnerships sharing one `sorting` value - copies, or records created by an
     * import - fall back to uid order. SQLite cannot make this assertion fail because uid
     * order is its natural order; it pins the contract for the DBMS where a tie is
     * otherwise resolved arbitrarily.
     */
    #[Test]
    public function partnershipsWithEqualSortingFallBackToUidOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipRepositoryFindByPid/partnershipsWithEqualSorting.csv');

        $this->assertSame([7, 8], $this->orderedResultUids($this->subject()->findByPid(2)));
    }

    private function subject(): PartnershipRepository
    {
        return $this->get(PartnershipRepository::class);
    }

    /**
     * Like {@see resultUids()}, but preserving the result order for the ordering tests.
     *
     * @param QueryResult<Partnership> $result
     * @return int[]
     */
    private function orderedResultUids(QueryResult $result): array
    {
        $uids = [];
        foreach ($result as $partnership) {
            $uids[] = (int)$partnership->getUid();
        }
        return $uids;
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
