<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Upgrades;

use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Localization\State;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * The partner coordinates became `allowLanguageSynchronization` (ACE-562), because a
 * place does not move when the page is translated. From now on the DataHandler keeps a
 * translation in step with its default record, and `Localization\State` treats a field
 * with no stored state as `parent`, so nothing has to be marked up for that to work.
 *
 * What synchronization cannot do is repair what is already stored. A partner translated
 * before geocoding ran carries its own - usually empty - coordinate, and nothing
 * re-saves it on its own. The map read that value and drew the partner at 0/0, or after
 * ACE-562 left it out of the map altogether, while the default record held perfectly
 * good coordinates.
 *
 * This copies the default record's coordinates onto its translations, once.
 *
 * Only live records are touched (`t3ver_wsid = 0`). A workspace version is a draft of a
 * record that is synchronized from now on anyway, and rewriting one behind the editor's
 * back would change a draft they did not touch.
 */
#[UpgradeWizard('academicPartners_synchronizePartnerCoordinates')]
final class SynchronizePartnerCoordinatesUpgradeWizard implements UpgradeWizardInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Synchronize academic partner coordinates with their translations.';
    }

    public function getDescription(): string
    {
        return 'Copies the geocoding coordinates of every academic partner page onto its '
            . 'translations. Translations created before the page was geocoded carry an '
            . 'empty coordinate of their own, which keeps the partner off the map in that '
            . 'language.';
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('pages');

        foreach ($this->outOfSyncTranslations() as $translation) {
            $connection->update(
                'pages',
                [
                    'geocode_latitude' => $translation['parent_latitude'],
                    'geocode_longitude' => $translation['parent_longitude'],
                ],
                ['uid' => (int)$translation['uid']],
            );
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->outOfSyncTranslations() !== [];
    }

    // Note: this reads the same rows `executeUpdate()` does rather than counting in
    // SQL, because "out of sync" is decided in PHP - see `outOfSyncTranslations()`.

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
     * Whether the editor detached either coordinate from its default record.
     */
    private function isDetached(string $l10nState): bool
    {
        if ($l10nState === '') {
            return false;
        }

        try {
            $states = json_decode($l10nState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }
        if (!is_array($states)) {
            return false;
        }

        return ($states['geocode_latitude'] ?? null) === State::STATE_CUSTOM
            || ($states['geocode_longitude'] ?? null) === State::STATE_CUSTOM;
    }

    /**
     * The comparison is done in PHP rather than in SQL: one side of it is regularly
     * `NULL`, and a null safe comparison is spelled differently on each of the four
     * supported platforms. Partner pages are counted in dozens, so reading them is
     * cheaper than the portability problem.
     *
     * @return list<array{uid: int, parent_latitude: string|null, parent_longitude: string|null}>
     */
    private function outOfSyncTranslations(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select(
                'translation.uid AS uid',
                'translation.geocode_latitude AS latitude',
                'translation.geocode_longitude AS longitude',
                'translation.l10n_state AS l10n_state',
                'parent.geocode_latitude AS parent_latitude',
                'parent.geocode_longitude AS parent_longitude',
            )
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
            ->executeQuery()
            ->fetchAllAssociative();

        $outOfSync = [];
        foreach ($rows as $row) {
            // An editor can detach a synchronized field deliberately, which writes
            // "custom" into `l10n_state`. Run late - after the upgrade, after people
            // have worked - this would otherwise revert that decision.
            if ($this->isDetached((string)($row['l10n_state'] ?? ''))) {
                continue;
            }

            if ((string)($row['latitude'] ?? '') === (string)($row['parent_latitude'] ?? '')
                && (string)($row['longitude'] ?? '') === (string)($row['parent_longitude'] ?? '')
            ) {
                continue;
            }

            $outOfSync[] = [
                'uid' => (int)$row['uid'],
                'parent_latitude' => $row['parent_latitude'],
                'parent_longitude' => $row['parent_longitude'],
            ];
        }

        return $outOfSync;
    }
}
