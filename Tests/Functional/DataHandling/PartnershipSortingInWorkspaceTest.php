<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\DataHandling;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A partnership is workspace aware, and editing one in a workspace creates a
 * version of it. `DataHandler::process_datamap()` does that through a **nested**
 * DataHandler running a `version` command, which reaches `versionizeRecord()` ->
 * `copyRecord_raw()` -> `insertNewCopyVersion()`: the whole database row is copied,
 * `role_sorting` included, and the pair is registered in `copyMappingArray_merged`
 * exactly the way a copy is.
 *
 * The cmdmap half of {@see \FGTCLB\AcademicPartners\Hook\PartnershipSortingHook}
 * must therefore tell a version from a copy. It would otherwise read "same parent,
 * same position as the record it came from" as a position inherited by a copy and
 * move the version to the end of the role's list - from an edit that never touched
 * the role, and permanently once the workspace is published.
 */
final class PartnershipSortingInWorkspaceTest extends AbstractAcademicPartnersTestCase
{
    private const TABLE_ROLE = 'tx_academicpartners_domain_model_role';
    private const TABLE_PARTNERSHIP = 'tx_academicpartners_domain_model_partnership';
    private const FIXTURE = __DIR__ . '/Fixtures/PartnershipSortingPerParent/twoPartnerPagesOneRoleWorkspace.csv';

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-workspaces',
    ];

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    /**
     * Editing a partnership in a workspace leaves its position in the role alone -
     * the version carries the position of the record it versions.
     */
    #[Test]
    public function editingAPartnershipInAWorkspaceKeepsItsPositionInTheRole(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->saveInWorkspace(self::TABLE_ROLE, 1, ['partnerships' => '2,4,1,3'], 0);
        $this->assertSame(3, $this->fetchRank(1), 'The fixture is not arranged as the assertion needs it.');

        $this->saveInWorkspace(self::TABLE_PARTNERSHIP, 1, ['partner' => 10], 1);

        $versionUid = $this->fetchVersionUid(1);
        $this->assertGreaterThan(0, $versionUid, 'Expected the edit to create a workspace version.');
        $this->assertSame(3, $this->fetchRank($versionUid), 'The workspace version was moved to the end of the role.');
        $this->assertSame(3, $this->fetchRank(1), 'The live record was moved.');
    }

    /**
     * A genuine copy made inside a workspace is still appended: it is a new record
     * of the role, not a version of one - `t3ver_oid` stays 0 and it carries the
     * NEW placeholder state.
     */
    #[Test]
    public function aPartnershipCopiedInAWorkspaceIsStillAppendedToTheRole(): void
    {
        $this->importCSVDataSet(self::FIXTURE);
        $this->saveInWorkspace(self::TABLE_ROLE, 1, ['partnerships' => '2,4,1,3'], 0);

        $copyUid = $this->copyInWorkspace(self::TABLE_PARTNERSHIP, 1, 10, 1);

        $this->assertGreaterThan(0, $copyUid, 'Expected the copy to be created.');
        $this->assertSame(5, $this->fetchRank($copyUid), 'The copy was not appended to the role.');
        $this->assertSame(3, $this->fetchRank(1), 'The record the copy was made from was moved.');
    }

    /**
     * @param array<string, int|string> $values
     */
    private function saveInWorkspace(string $tableName, int $uid, array $values, int $workspaceId): void
    {
        $dataHandler = $this->startDataHandler($workspaceId);
        $dataHandler->start([$tableName => [$uid => $values]], []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    private function copyInWorkspace(string $tableName, int $uid, int $targetPageUid, int $workspaceId): int
    {
        $dataHandler = $this->startDataHandler($workspaceId);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function startDataHandler(int $workspaceId): DataHandler
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        return GeneralUtility::makeInstance(DataHandler::class);
    }

    private function fetchVersionUid(int $liveUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('uid')
            ->from(self::TABLE_PARTNERSHIP)
            ->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($liveUid, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function fetchRank(int $uid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('role_sorting')
            ->from(self::TABLE_PARTNERSHIP)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
