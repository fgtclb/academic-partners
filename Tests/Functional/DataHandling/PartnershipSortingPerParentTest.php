<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\DataHandling;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A partnership is an inline child of two records at once: of its partner page,
 * which owns the order the frontend renders, and of its role.
 *
 * `RelationHandler::writeForeignField()` numbers the children of the saved parent
 * 1..n into the sort field the relation declares, so both relations wrote the
 * shared `sorting` column until the role relation gained `role_sorting`. Saving a
 * role therefore renumbered its partnerships across every partner page that owns
 * one of them, rearranging lists an editor had arranged on the partner page.
 *
 * The fixture is built so the assertions fail on every DBMS when the sort column is
 * shared: the role lists the partnerships of two pages in an order that contradicts
 * the order of both pages.
 */
final class PartnershipSortingPerParentTest extends AbstractAcademicPartnersTestCase
{
    private const TABLE_ROLE = 'tx_academicpartners_domain_model_role';
    private const TABLE_PARTNERSHIP = 'tx_academicpartners_domain_model_partnership';

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    /**
     * Saving the role in an order that contradicts both partner pages leaves the
     * order of each page untouched.
     */
    #[Test]
    public function savingARoleKeepsThePartnershipOrderOfEveryPartnerPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');

        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $this->assertSame(
            [1, 2],
            $this->fetchChildUids('page', 10, 'sorting'),
            'Saving the role rearranged the partnerships of partner page 10.',
        );
        $this->assertSame(
            [3, 4],
            $this->fetchChildUids('page', 11, 'sorting'),
            'Saving the role rearranged the partnerships of partner page 11.',
        );
    }

    /**
     * The role keeps the arrangement it was saved with, in a sort column of its own.
     */
    #[Test]
    public function savingARoleStoresItsOwnArrangement(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');

        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'The role did not keep the order it was saved with.',
        );
    }

    /**
     * Saving a partner page still writes the shared `sorting` column - the order the
     * frontend renders - and leaves the arrangement of the role alone.
     */
    #[Test]
    public function savingAPartnerPageKeepsTheArrangementOfTheRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $this->saveInlineChildren('pages', 10, 'tx_academicpartners_partnerships', [2, 1]);

        $this->assertSame(
            [2, 1],
            $this->fetchChildUids('page', 10, 'sorting'),
            'Saving the partner page did not rearrange its own partnerships.',
        );
        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'Saving the partner page rearranged the partnerships of the role.',
        );
    }

    /**
     * The path editors actually use: a partnership is created in the inline list of
     * its partner page and picks its role in the `role` select of its own form. The
     * role is not saved, so nothing renumbers its list - the new partnership has to
     * be appended to it, not left at 0 above everything else.
     */
    #[Test]
    public function aPartnershipCreatedOnThePartnerPageIsAppendedToItsRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $newUid = $this->createPartnershipOnPartnerPage(10, 1);

        $this->assertSame(
            [2, 4, 1, 3, $newUid],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'The new partnership was not appended to the list of its role.',
        );
    }

    /**
     * Two of them in a row land behind each other rather than tying: the data map is
     * processed record by record, so the second one sees the first one stored. The
     * ranks themselves are asserted, not the resulting order - two records tied at 0
     * come back in uid order on SQLite and would hide the defect.
     */
    #[Test]
    public function twoPartnershipsCreatedInARowDoNotTie(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $firstUid = $this->createPartnershipOnPartnerPage(10, 1);
        $secondUid = $this->createPartnershipOnPartnerPage(11, 1);

        $this->assertSame(5, $this->fetchColumn(self::TABLE_PARTNERSHIP, $firstUid, 'role_sorting'));
        $this->assertSame(6, $this->fetchColumn(self::TABLE_PARTNERSHIP, $secondUid, 'role_sorting'));
    }

    /**
     * A partnership that changes its role is appended to the new one: the rank it had
     * in the old role says nothing about where it belongs in the new list.
     */
    #[Test]
    public function aPartnershipThatChangesItsRoleIsAppendedToTheNewOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);
        $this->updateRecord(self::TABLE_PARTNERSHIP, 3, ['role' => 2]);

        $this->updateRecord(self::TABLE_PARTNERSHIP, 1, ['role' => 2]);

        $this->assertSame([2, 4], $this->fetchChildUids('role', 1, 'role_sorting'));
        $this->assertSame([3, 1], $this->fetchChildUids('role', 2, 'role_sorting'));
    }

    /**
     * Saving a partnership without touching its role leaves its rank alone - that is
     * what makes an arrangement made in the role form survive. It is the guard
     * against the hook being too eager, so unlike its siblings it passes without the
     * hook as well.
     */
    #[Test]
    public function savingAPartnershipKeepsItsRankInTheRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $this->updateRecord(self::TABLE_PARTNERSHIP, 1, ['partner' => 10]);

        $this->assertSame([2, 4, 1, 3], $this->fetchChildUids('role', 1, 'role_sorting'));
    }

    /**
     * Clearing the role resets the column: a rank in a list the record left would be
     * restored together with the role by some later, unrelated save.
     */
    #[Test]
    public function clearingTheRoleResetsTheRank(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $this->updateRecord(self::TABLE_PARTNERSHIP, 1, ['role' => 0]);

        $this->assertSame(0, $this->fetchColumn(self::TABLE_PARTNERSHIP, 1, 'role_sorting'));
        $this->assertSame([2, 4, 3], $this->fetchChildUids('role', 1, 'role_sorting'));
    }

    /**
     * A `copy` command on the partnership itself runs `copyRecord()`, which submits
     * a nested data map - so this one is ranked by the data map half. The path the
     * cmdmap half exists for is the cascaded one, below.
     */
    #[Test]
    public function aCopiedPartnershipIsAppendedToItsRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $copyUid = $this->copyRecord(self::TABLE_PARTNERSHIP, 1, 10);

        $this->assertSame(
            [2, 4, 1, 3, $copyUid],
            $this->fetchChildUids('role', 1, 'role_sorting'),
        );
    }

    /**
     * Creates a partnership the way the partner page form does: the record carries the
     * role in its own `role` select, and the page relation lists it.
     */
    private function createPartnershipOnPartnerPage(int $pageUid, int $roleUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $newId = StringUtility::getUniqueId('NEW');
        $existingUids = $this->fetchChildUids('page', $pageUid, 'sorting');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_PARTNERSHIP => [
                    $newId => ['pid' => $pageUid, 'page' => $pageUid, 'partner' => $pageUid, 'role' => $roleUid],
                ],
                'pages' => [
                    $pageUid => [
                        'tx_academicpartners_partnerships' => implode(',', [...$existingUids, $newId]),
                    ],
                ],
            ],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)$dataHandler->substNEWwithIDs[$newId];
    }

    /**
     * @param array<string, int|string> $values
     */
    private function updateRecord(string $tableName, int $uid, array $values): void
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([$tableName => [$uid => $values]], [], $backendUser);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    private function copyRecord(string $tableName, int $uid, int $targetPageUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function fetchColumn(string $tableName, int $uid, string $columnName): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select($columnName)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Copying the **owning** parent is the path the cmdmap half of the hook exists
     * for, and the one a direct copy does not exercise: a cascaded inline child is
     * created by `copyRecord_raw()` -> `insertDB()` with the full database row, no
     * data map involved and nothing that drops a column without a TCA `columns`
     * entry - so the copy arrives carrying the rank of the record it was copied
     * from, and ties with it in the role's list.
     */
    #[Test]
    public function copyingAPartnerPageAppendsTheCopiedPartnershipsToTheirRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRole.csv');
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'partnerships', [2, 4, 1, 3]);

        $copies = $this->copyRecordWithMapping('pages', 10, 1, self::TABLE_PARTNERSHIP);

        $this->assertCount(2, $copies, 'Expected both partnerships of the page to be copied.');
        $ranks = $this->fetchRanks('role', 1, 'role_sorting');
        $this->assertSame(
            array_values(array_unique($ranks)),
            array_values($ranks),
            'Two partnerships of the role share a position: ' . json_encode($ranks),
        );
        // The copies are appended in the order their originals hold in the role,
        // not in the order the copy run happened to create them: partnership 2 sits
        // before partnership 1 there, so its copy does too.
        $this->assertSame(
            [2, 4, 1, 3, $copies[2], $copies[1]],
            $this->fetchChildUids('role', 1, 'role_sorting'),
        );
    }

    /**
     * @return array<int, int> The records of $childTable the run created, source uid => new uid.
     */
    private function copyRecordWithMapping(string $tableName, int $uid, int $targetPageUid, string $childTable): array
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        // Copying a page reaches DataHandler::getLanguageService(), which is typed
        // against $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        $mapping = [];
        foreach ($dataHandler->copyMappingArray_merged[$childTable] ?? [] as $sourceUid => $newUid) {
            $mapping[(int)$sourceUid] = (int)$newUid;
        }
        return $mapping;
    }

    /**
     * @return array<int, int> uid => rank, in the order the sort column puts them in.
     */
    private function fetchRanks(string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', $sortField)
            ->from(self::TABLE_PARTNERSHIP)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $ranks = [];
        foreach ($rows as $row) {
            $ranks[(int)$row['uid']] = (int)$row[$sortField];
        }
        return $ranks;
    }

    /**
     * @param list<int> $childUids
     */
    private function saveInlineChildren(string $tableName, int $uid, string $fieldName, array $childUids): void
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [$tableName => [$uid => [$fieldName => implode(',', $childUids)]]],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * The uids of the partnerships of one parent, in the order the given sort column
     * puts them in - `uid` settling ties, the way the inline relation reads them.
     *
     * @return list<int>
     */
    private function fetchChildUids(string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_PARTNERSHIP)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }
}
