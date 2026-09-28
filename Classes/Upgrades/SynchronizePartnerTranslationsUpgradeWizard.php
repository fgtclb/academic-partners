<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_partners" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPartners\Upgrades;

use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Localization\State;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Two columns of a partner page are `allowLanguageSynchronization`: the coordinates
 * (ACE-709), because a place does not move when the page is translated, and "Show on map"
 * (ACE-770), because the partner map reads the switch of the page language. From now on
 * the DataHandler keeps a translation in step with its default record, and
 * `Localization\State` treats a field with no stored state as `parent`, so nothing has to
 * be marked up for that to work.
 *
 * What synchronization cannot do is repair what is already stored, and nothing re-saves
 * it on its own:
 *
 * - A partner translated before geocoding ran carries its own, usually empty,
 *   coordinate. The map drew it at 0/0, or after ACE-708 taught the module to refuse
 *   the pair left it out of the map, while the default record held perfectly good
 *   coordinates.
 * - Nothing read "Show on map" before ACE-770, so a translation made while its default
 *   record was switched off still holds that `0` after the default record was switched
 *   on again. The partner would leave the map of that language with the update.
 *
 * This copies the values of the default record onto its translations, once. Each group is
 * synchronized on its own: the coordinates as a pair, "Show on map" by itself, and a group
 * an editor detached keeps its values while the other group is still synchronized.
 *
 * Only live records are touched (`t3ver_wsid = 0`). A workspace version is a draft of a
 * record that is synchronized from now on anyway, and rewriting one behind the editor's
 * back would change a draft they did not touch.
 */
#[UpgradeWizard('academicPartners_synchronizePartnerTranslations')]
final class SynchronizePartnerTranslationsUpgradeWizard implements UpgradeWizardInterface
{
    /**
     * The groups of columns synchronized together. A group is detached when the editor
     * detached any of its columns: half a coordinate pair is not a place.
     */
    private const GROUPS = [
        'coordinates' => ['geocode_latitude', 'geocode_longitude'],
        'showOnMap' => ['show_on_map'],
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Synchronize academic partner pages with their translations.';
    }

    public function getDescription(): string
    {
        return 'Copies the geocoding coordinates and the "Show on map" switch of every '
            . 'academic partner page onto its translations. A translation created before the '
            . 'page was geocoded carries an empty coordinate of its own, and one created while '
            . 'the partner was hidden from the map still carries that switch, which keeps '
            . 'the partner off the map in that language.';
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('pages');

        foreach ($this->outOfSyncTranslations() as $uid => $values) {
            $connection->update('pages', $values, ['uid' => $uid]);
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->outOfSyncTranslations() !== [];
    }

    /**
     * @return array<class-string>
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * The columns an editor detached from the default record.
     *
     * @return list<string>
     */
    private function detachedColumns(string $l10nState): array
    {
        if ($l10nState === '') {
            return [];
        }

        try {
            $states = json_decode($l10nState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
        if (!is_array($states)) {
            return [];
        }

        return array_keys(array_filter(
            $states,
            static fn(mixed $state): bool => $state === State::STATE_CUSTOM,
        ));
    }

    /**
     * The values each translation has to take over, by translation uid.
     *
     * The comparison is done in PHP rather than in SQL: one side of it is regularly `NULL`,
     * and a null safe comparison is spelled differently on each of the four supported
     * platforms. Partner pages are counted in dozens, so reading them is cheaper than the
     * portability problem.
     *
     * @return array<int, array<string, mixed>>
     */
    private function outOfSyncTranslations(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $columns = array_merge(...array_values(self::GROUPS));
        $select = ['translation.uid AS uid', 'translation.l10n_state AS l10n_state'];
        foreach ($columns as $column) {
            $select[] = 'translation.' . $column . ' AS ' . $column;
            $select[] = 'parent.' . $column . ' AS parent_' . $column;
        }

        $rows = $queryBuilder
            ->select(...$select)
            ->from('pages', 'translation')
            ->innerJoin(
                'translation',
                'pages',
                'parent',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'translation.l10n_parent',
                        $queryBuilder->quoteIdentifier('parent.uid'),
                    ),
                    // The parent has to be a live, undeleted record too: a translation
                    // of a deleted partner must not inherit the deleted row's values.
                    $queryBuilder->expr()->eq(
                        'parent.deleted',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->eq(
                        'parent.t3ver_wsid',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                    ),
                ),
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'translation.doktype',
                    $queryBuilder->createNamedParameter(PageTypes::ACADEMIC_PARTNERS, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->gt(
                    'translation.sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'translation.deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'translation.t3ver_wsid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            )
            ->orderBy('translation.uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $outOfSync = [];
        foreach ($rows as $row) {
            // An editor can detach a synchronized field deliberately, which writes
            // "custom" into `l10n_state`. Run late - after the upgrade, after people
            // have worked - this would otherwise revert that decision.
            $detached = $this->detachedColumns((string)($row['l10n_state'] ?? ''));
            $values = [];
            foreach (self::GROUPS as $group) {
                if (array_intersect($group, $detached) !== []) {
                    continue;
                }
                foreach ($group as $column) {
                    if ((string)($row[$column] ?? '') !== (string)($row['parent_' . $column] ?? '')) {
                        // The whole group, so a pair is never half copied.
                        foreach ($group as $groupColumn) {
                            $values[$groupColumn] = $row['parent_' . $groupColumn];
                        }
                        break;
                    }
                }
            }
            if ($values !== []) {
                $outOfSync[(int)$row['uid']] = $values;
            }
        }

        return $outOfSync;
    }
}
