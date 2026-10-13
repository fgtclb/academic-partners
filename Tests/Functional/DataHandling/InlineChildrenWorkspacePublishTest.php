<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\DataHandling;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\WorkspacePublishingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * A partnership keeps its page and its role when either is edited in a workspace and
 * published, or copied (ACE-907).
 *
 * The first workspace edit of a page or a role versions its partnerships in the same
 * command. Core rewrote the pointer of each partnership version to the version row of
 * the page or role, as it does for every relation of a new version. Published from
 * the workspace module, core writes the live parent into the partnerships once more
 * after the swap, unless the page and the role were both edited in the workspace.
 * Published from live, as the scheduled publishing of a workspace does, it never
 * does. The published partnership then kept the pointer to the deleted version row:
 * it belonged to no page, or had no role.
 */
final class InlineChildrenWorkspacePublishTest extends AbstractAcademicPartnersTestCase
{
    use WorkspacePublishingTrait;

    private const TABLE_PARTNERSHIP = 'tx_academicpartners_domain_model_partnership';

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildrenWorkspacePublish/pageWithPartnership.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{string, int, array<string, string>, int, string}>
     */
    public static function parents(): \Generator
    {
        yield 'page of a partnership' => ['pages', 2, ['title' => 'Home changed'], 1, 'page'];
        yield 'role of a partnership' => ['tx_academicpartners_domain_model_role', 1, ['name' => 'Research'], 3, 'role'];
    }

    /**
     * @return \Generator<string, array{string, int, array<string, string>, int, string, bool}>
     */
    public static function parentsPublishedFromBothPlaces(): \Generator
    {
        foreach (self::parents() as $name => $parent) {
            yield $name . ', published from the workspace' => [...$parent, true];
            yield $name . ', published from live' => [...$parent, false];
        }
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function publishingPlaces(): array
    {
        return [
            'published from the workspace' => [true],
            'published from live' => [false],
        ];
    }

    /**
     * The version of the partnership points at the live page or role, so does the
     * published partnership, and a second edit versions it with its parent again.
     *
     * @param array<string, string> $change
     */
    #[Test]
    #[DataProvider('parentsPublishedFromBothPlaces')]
    public function aPartnershipKeepsItsParentWhenTheParentIsPublished(
        string $parentTable,
        int $parentUid,
        array $change,
        int $copyTarget,
        string $pointerColumn,
        bool $fromTheWorkspace,
    ): void {
        $this->runDataHandlerInWorkspace(1, [$parentTable => [$parentUid => $change]]);

        $partnershipVersionUid = $this->getWorkspaceVersionUid(self::TABLE_PARTNERSHIP, 1, 1);
        $this->assertGreaterThan(0, $partnershipVersionUid, 'Expected the partnership to be versioned with its parent.');
        $this->assertSame($parentUid, $this->getPointer($partnershipVersionUid, $pointerColumn), 'The version of the partnership points at another parent.');

        $this->publishWorkspace(1, $fromTheWorkspace);

        $this->assertSame($parentUid, $this->getPointer(1, $pointerColumn), 'The published partnership points at another parent.');

        $this->runDataHandlerInWorkspace(1, [$parentTable => [$parentUid => [array_key_first($change) => 'Changed again']]]);

        $this->assertGreaterThan(0, $this->getWorkspaceVersionUid(self::TABLE_PARTNERSHIP, 1, 1), 'A second edit did not version the partnership with its parent.');
    }

    /**
     * The page and the role edited in the same workspace. Published from the workspace,
     * core used to leave the partnership pointing at the deleted version row of the page.
     */
    #[Test]
    #[DataProvider('publishingPlaces')]
    public function aPartnershipKeepsPageAndRoleEditedInTheSameWorkspace(bool $fromTheWorkspace): void
    {
        $this->runDataHandlerInWorkspace(1, [
            'pages' => [2 => ['title' => 'Home changed']],
            'tx_academicpartners_domain_model_role' => [1 => ['name' => 'Changed']],
        ]);

        $this->publishWorkspace(1, $fromTheWorkspace);

        $this->assertSame(2, $this->getPointer(1, 'page'), 'The published partnership points at another page.');
        $this->assertSame(1, $this->getPointer(1, 'role'), 'The published partnership points at another role.');
    }

    /**
     * @param array<string, string> $change
     */
    #[Test]
    #[DataProvider('parents')]
    public function aCopiedParentGetsACopiedPartnership(
        string $parentTable,
        int $parentUid,
        array $change,
        int $copyTarget,
        string $pointerColumn,
    ): void {
        $dataHandler = $this->runDataHandlerInWorkspace(0, [], [$parentTable => [$parentUid => ['copy' => $copyTarget]]]);

        $copiedParentUid = (int)($dataHandler->copyMappingArray_merged[$parentTable][$parentUid] ?? 0);
        $copiedPartnershipUid = (int)($dataHandler->copyMappingArray_merged[self::TABLE_PARTNERSHIP][1] ?? 0);
        $this->assertGreaterThan(0, $copiedParentUid, 'Expected the parent to be copied.');
        $this->assertGreaterThan(0, $copiedPartnershipUid, 'Expected the partnership to be copied with its parent.');
        $this->assertSame($copiedParentUid, $this->getPointer($copiedPartnershipUid, $pointerColumn));
        $this->assertSame($parentUid, $this->getPointer(1, $pointerColumn));
    }

    private function getPointer(int $partnershipUid, string $pointerColumn): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_PARTNERSHIP);
        $queryBuilder->getRestrictions()->removeAll();
        $pointer = $queryBuilder
            ->select($pointerColumn)
            ->from(self::TABLE_PARTNERSHIP)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($partnershipUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        $this->assertNotFalse($pointer, sprintf('Partnership %d does not exist.', $partnershipUid));

        return (int)$pointer;
    }
}
