<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_partners" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPartners\Hook;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Keeps `role_sorting` - the sort column of the role relation - filled, for every
 * partnership that joins a role outside that role's own form.
 *
 * `RelationHandler::writeForeignField()` numbers the children of the record being
 * saved, so it fills the column only when the **role** is saved. The usual way a
 * partnership gets its role is the other one: the `role` select of the partnership
 * form, which editors reach through the inline list of the partner page. Without
 * this hook such a partnership keeps `0` and sorts above every numbered sibling in
 * the role's list - and two of them tie, which is exactly the arbitrary order the
 * sort column exists to remove.
 *
 * The rule is the one an inline list follows anyway: **a partnership that joins a
 * role is appended to the end of that role's list.** It applies when the record is
 * created with a role, when its role changes (the rank it had in the old role means
 * nothing in the new one), and when it is copied or localized. A partnership that
 * already has a rank in the role it is being saved with keeps it, so an arrangement
 * made in the role form survives every later save of the partnership. Clearing the
 * role resets the column to `0`.
 *
 * Two entry points, because a copy never passes through the data map:
 *
 * - `processDatamap_postProcessFieldArray()` writes the rank into the field array
 *   before the record is stored, so it costs no statement of its own. It runs per
 *   record, in the order the data map is processed, so a second new partnership of
 *   the same role sees the first one already stored and lands behind it.
 * - `processCmdmap_afterFinish()` catches what a copy or localize run created
 *   **without** a data map. A `copy` or `localize` command on the record itself
 *   runs `copyRecord()`, which submits a nested data map, so the half above
 *   already ranks it. A cascaded inline child - the owning parent is copied -
 *   takes the other branch: `copyRecord_raw()` -> `insertDB()` with the full
 *   database row, which carries the new columns over verbatim, so the copy would
 *   tie with the record it was copied from. `DataHandler::$copyMappingArray_merged`
 *   holds every record the run created as `source uid => new uid`, which is what
 *   makes that position recognisable as inherited rather than earned.
 *
 * Registered in `ext_localconf.php` as a `processDatamapClass` and a
 * `processCmdmapClass`. The class is a public, constructor-injected service and
 * holds no state of its own.
 */
final class PartnershipSortingHook
{
    private const TABLE = 'tx_academicpartners_domain_model_partnership';

    /**
     * The secondary inline relations of the table, as parent column => sort column.
     * The partner page relation is not among them: it owns the shared `sorting`
     * column, which the core fills for every record.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    private const RELATIONS = [
        'role' => 'role_sorting',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @param array<string, mixed> $fieldArray
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        string|int $id,
        array &$fieldArray,
        DataHandler $dataHandler,
    ): void {
        if ($table !== self::TABLE) {
            return;
        }
        $storedRow = $status === 'update' ? $this->fetchRow((int)$id) : [];
        foreach (self::RELATIONS as $parentField => $sortField) {
            $rank = $this->determineRank($parentField, $sortField, $fieldArray, $storedRow);
            if ($rank !== null) {
                $fieldArray[$sortField] = $rank;
            }
        }
    }

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $createdUids = $dataHandler->copyMappingArray_merged[self::TABLE] ?? [];
        if (!is_array($createdUids)) {
            return;
        }
        /** @var array<string, list<array{uid: int, parentField: string, sortField: string, parentUid: int, inheritedRank: int}>> $groups */
        $groups = [];
        foreach ($createdUids as $sourceUid => $createdUid) {
            $uid = (int)$createdUid;
            if ($uid <= 0) {
                continue;
            }
            $row = $this->fetchRow($uid);
            if ($row === [] || (int)($row['t3ver_oid'] ?? 0) === (int)$sourceUid) {
                // A workspace **version** of the source rather than a copy of it.
                // Editing a record in a workspace versions it through the same
                // copyRecord_raw() path and registers the pair here, so it arrives
                // looking exactly like a cascaded copy - but its position is the one
                // it versions, and moving it would rearrange the list on publish.
                continue;
            }
            $sourceRow = $this->fetchRow((int)$sourceUid);
            foreach (self::RELATIONS as $parentField => $sortField) {
                if (!$this->hasInheritedItsPosition($row, $sourceRow, $parentField, $sortField)) {
                    continue;
                }
                $parentUid = (int)$row[$parentField];
                $groups[$sortField . ':' . $parentUid][] = [
                    'uid' => $uid,
                    'parentField' => $parentField,
                    'sortField' => $sortField,
                    'parentUid' => $parentUid,
                    'inheritedRank' => (int)($row[$sortField] ?? 0),
                ];
            }
        }
        foreach ($groups as $group) {
            $this->appendGroup($group);
        }
    }

    /**
     * Appends the records of one list in one go, in the order they inherited, so the
     * result does not depend on the order `copyMappingArray_merged` happens to hold
     * them in. Their inherited positions are excluded from the ceiling: they are
     * positions of the records they were copied from, or - when the parent itself was
     * copied in the same run - of a list that holds nothing else, in which case this
     * reproduces exactly the 1..n the core assigned.
     *
     * @param list<array{uid: int, parentField: string, sortField: string, parentUid: int, inheritedRank: int}> $group
     */
    private function appendGroup(array $group): void
    {
        usort(
            $group,
            static fn(array $a, array $b): int => [$a['inheritedRank'], $a['uid']] <=> [$b['inheritedRank'], $b['uid']],
        );
        $rank = $this->positionToAppend(
            $group[0]['parentField'],
            $group[0]['sortField'],
            $group[0]['parentUid'],
            array_map(static fn(array $candidate): int => $candidate['uid'], $group),
        );
        if ($rank === null) {
            return;
        }
        $rank--;
        foreach ($group as $candidate) {
            $this->writeRank($candidate['sortField'], $candidate['uid'], ++$rank);
        }
    }

    /**
     * Whether the created record arrived without a position of its own in the list
     * it now belongs to.
     *
     * It did when it carries no position at all, when it is in a different list than
     * the record it was copied from - the parent was copied in the same run and
     * remapped - and when it carries exactly the position of that record, which is
     * what `insertDB()` stores for a cascaded child. A record the data map half has
     * already appended has none of those, and is left alone rather than written a
     * second time.
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $sourceRow
     */
    private function hasInheritedItsPosition(array $row, array $sourceRow, string $parentField, string $sortField): bool
    {
        $parentUid = (int)($row[$parentField] ?? 0);
        if ($parentUid <= 0) {
            return false;
        }
        $rank = (int)($row[$sortField] ?? 0);
        return $rank === 0
            || $parentUid !== (int)($sourceRow[$parentField] ?? 0)
            || $rank === (int)($sourceRow[$sortField] ?? 0);
    }

    /**
     * The rank the record has to be stored with, or `null` when the column is to be
     * left as it is.
     *
     * @param array<string, mixed> $fieldArray The field array of this write; it holds
     *                                         only the columns the write touches.
     * @param array<string, mixed> $storedRow  The record as it is stored, empty for a
     *                                         record that does not exist yet.
     */
    private function determineRank(
        string $parentField,
        string $sortField,
        array $fieldArray,
        array $storedRow,
    ): ?int {
        $parentIsWritten = array_key_exists($parentField, $fieldArray);
        if ($parentIsWritten && !MathUtility::canBeInterpretedAsInteger($fieldArray[$parentField])) {
            // A parent that is still a "NEW..." placeholder. The core strips such a
            // value from the field array and writes it from processRemapStack()
            // afterwards, with a plain updateDB() no hook sees, so the record keeps
            // no position for now - and is appended by the next save of it. Not
            // reachable from a backend form; these selects offer no "create new".
            return null;
        }
        $storedParentUid = (int)($storedRow[$parentField] ?? 0);
        $parentUid = $parentIsWritten ? (int)$fieldArray[$parentField] : $storedParentUid;
        if ($parentUid <= 0) {
            // The record has no such parent any more; a rank in a list it left would
            // be restored together with the parent by a later, unrelated save.
            return (int)($storedRow[$sortField] ?? 0) > 0 ? 0 : null;
        }
        $keepsItsParent = $parentUid === $storedParentUid;
        $currentRank = $keepsItsParent ? (int)($storedRow[$sortField] ?? 0) : 0;
        if ($currentRank > 0) {
            return null;
        }
        return $this->positionToAppend($parentField, $sortField, $parentUid);
    }

    /**
     * The state of a parent's list: the highest position taken in it, and how many
     * other rows it holds. Every row that carries the parent counts - deleted ones
     * and workspace rows included, the same set the upgrade wizard numbers. Records
     * that are about to be given a position are excluded, so a position they brought
     * along does not raise the ceiling they are measured against.
     *
     * @param list<int> $excludedUids
     * @return array{highest: int, others: int}
     */
    private function listState(string $parentField, string $sortField, int $parentUid, array $excludedUids = []): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->selectLiteral(
                'MAX(' . $queryBuilder->quoteIdentifier($sortField) . ') AS ' . $queryBuilder->quoteIdentifier('highest_rank'),
                'COUNT(' . $queryBuilder->quoteIdentifier('uid') . ') AS ' . $queryBuilder->quoteIdentifier('other_rows'),
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            );
        if ($excludedUids !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($excludedUids),
                ),
            );
        }
        $row = $queryBuilder->executeQuery()->fetchAssociative();
        return [
            'highest' => (int)($row['highest_rank'] ?? 0),
            'others' => (int)($row['other_rows'] ?? 0),
        ];
    }

    /**
     * The position to append to a list, or `null` while that list has none to append
     * to.
     *
     * A list whose rows all sit at `0` has not been seeded yet: the upgrade wizard
     * has not run, and it is the wizard that knows the order those rows are in
     * today. Numbering one of them now - because it happened to be saved first -
     * would make the wizard treat it as arranged and append the rest behind it,
     * which is precisely the rearrangement this change exists to prevent. So the
     * record is left at `0` and the wizard seeds the whole list at once. The only
     * row that may take position 1 is the one that has no siblings at all, which is
     * a list the wizard has nothing to say about.
     *
     * @param list<int> $excludedUids
     */
    private function positionToAppend(string $parentField, string $sortField, int $parentUid, array $excludedUids = []): ?int
    {
        $state = $this->listState($parentField, $sortField, $parentUid, $excludedUids);
        if ($state['highest'] === 0 && $state['others'] > 0) {
            return null;
        }
        return $state['highest'] + 1;
    }

    private function writeRank(string $sortField, int $uid, int $rank): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->set($sortField, $rank, true, Connection::PARAM_INT)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                ),
            )
            ->executeStatement();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }
        return BackendUtility::getRecord(self::TABLE, $uid, '*', '', false) ?? [];
    }
}
