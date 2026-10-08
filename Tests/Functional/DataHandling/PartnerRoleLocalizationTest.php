<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\DataHandling;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Localizing a partner role localizes no partnership (ACE-874).
 *
 * A partnership is translated with its partner page and keeps the role of its default
 * language record. `DataHandler::localize()` copies every inline child of a localized
 * record all the same: it created a translation of every partnership the role lists,
 * attached to the role translation, and one more copy of a partnership that is
 * translated or valid in all languages already, so a partner page showed it twice.
 *
 * The fixture lists four partnerships on role 1: partnership 1 with its translation 3,
 * the untranslated partnership 2, and partnership 4, valid in all languages.
 */
final class PartnerRoleLocalizationTest extends AbstractAcademicPartnersTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const TABLE_ROLE = 'tx_academicpartners_domain_model_role';
    private const TABLE_PARTNERSHIP = 'tx_academicpartners_domain_model_partnership';

    /**
     * The partnerships as the fixture stores them.
     */
    private const PARTNERSHIPS = [
        ['uid' => 1, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
        ['uid' => 2, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
        ['uid' => 3, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'page' => 10, 'role' => 1],
        ['uid' => 4, 'sys_language_uid' => -1, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'partner-role-localization-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerRoleLocalization/roleWithPartnerships.csv');
        $this->setUpBackendUser(1);
        // The DataHandler reads the language service from $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * `localize` connects the role translation to its default record, `copyToLanguage`
     * copies the role into the language without a connection. Both copy the inline
     * children the same way.
     *
     * @return array<string, array{0: string}>
     */
    public static function languageCommandProvider(): array
    {
        return [
            'localize' => ['localize'],
            'copy to language' => ['copyToLanguage'],
        ];
    }

    #[Test]
    #[DataProvider('languageCommandProvider')]
    public function localizingARoleLeavesItsPartnershipsAlone(string $command): void
    {
        $roleTranslationUid = $this->localizeRole($command);

        $this->assertGreaterThan(1, $roleTranslationUid);
        $this->assertSame(self::PARTNERSHIPS, $this->fetchPartnerships());
        $this->assertSame(0, $this->countCreatedRecordsNotDeleted(), 'A partnership was created and left behind.');
    }

    /**
     * The translation the editor saves next creates no partnership either.
     */
    #[Test]
    public function savingTheLocalizedRoleLeavesItsPartnershipsAlone(): void
    {
        $roleTranslationUid = $this->localizeRole('localize');

        $this->processDataMap([self::TABLE_ROLE => [$roleTranslationUid => ['name' => 'Koordination']]]);
        $this->processDataMap([self::TABLE_ROLE => [$roleTranslationUid => ['name' => 'Koordinierung']]]);

        $this->assertSame(self::PARTNERSHIPS, $this->fetchPartnerships());
    }

    /**
     * A plain copy of a role in its own language is not a localization and keeps copying
     * its partnerships, as before.
     */
    #[Test]
    public function copyingARoleStillCopiesItsPartnerships(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_ROLE => [1 => ['copy' => 100]]]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog);
        $roleCopyUid = (int)($dataHandler->copyMappingArray_merged[self::TABLE_ROLE][1] ?? 0);

        $this->assertGreaterThan(1, $roleCopyUid);
        $this->assertNotSame([], array_filter(
            $this->fetchPartnerships(),
            static fn(array $partnership): bool => $partnership['role'] === $roleCopyUid,
        ));
    }

    /**
     * The core still tries to localize partnership 1 with the role and refuses, because
     * the partnership is translated already. That message is the only one the run reports.
     */
    private function localizeRole(string $command): int
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_ROLE => [1 => [$command => 1]]]);
        $dataHandler->process_cmdmap();
        $this->assertCount(1, $dataHandler->errorLog, implode(PHP_EOL, $dataHandler->errorLog));
        $this->assertStringContainsString(
            'There already are localizations (3) for language 1 of the "' . self::TABLE_PARTNERSHIP . '" record 1',
            $dataHandler->errorLog[0],
        );

        return (int)($dataHandler->copyMappingArray_merged[self::TABLE_ROLE][1] ?? 0);
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $dataMap
     */
    private function processDataMap(array $dataMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * @return list<array<string, int>> The partnerships that are not deleted, in uid order.
     */
    private function fetchPartnerships(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent', 'page', 'role')
            ->from(self::TABLE_PARTNERSHIP)
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => array_map(intval(...), $row),
            $rows,
        );
    }

    /**
     * The guard removes a created record with a soft delete on this branch, so a removed
     * one stays in the table as deleted.
     */
    private function countCreatedRecordsNotDeleted(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_PARTNERSHIP)
            ->where(
                $queryBuilder->expr()->gt('uid', 4),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
