<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compiles the backend form of a partnership record and asserts which partners the
 * select offers: every partner page that is not deleted, whether it is hidden or
 * outside its start or end time (ACE-672).
 *
 * The partner field has no `foreign_table`, so core does not restore a stored value
 * that is missing from the items. The compiled row then holds no partner, the browser
 * submits the first entry of the select and the save writes the partner away. Offering
 * every partner that is not deleted is what keeps a partnership whose partner is not
 * visible intact.
 *
 * The fixture holds one partner page of each kind: visible (10), hidden (11), with a
 * start time in the future (12), with an end time in the past (13) and deleted (14),
 * and one partnership referring to each of the first four.
 */
final class PartnerSelectVisibilityTest extends AbstractAcademicPartnersTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerSelectVisibility/partners.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function everyPartnerThatIsNotDeletedIsOffered(): void
    {
        $this->assertSame(
            ['Expired Partner', 'Future Partner', 'Hidden Partner', 'Visible Partner'],
            $this->offeredPartnerLabels(),
        );
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function partnershipsWithAPartnerThatIsNotVisible(): array
    {
        return [
            'hidden partner' => [2, 11],
            'partner before its start time' => [3, 12],
            'partner after its end time' => [4, 13],
        ];
    }

    #[Test]
    #[DataProvider('partnershipsWithAPartnerThatIsNotVisible')]
    public function theFormShowsAPartnerThatIsNotVisibleAsChosen(int $partnershipUid, int $partnerUid): void
    {
        $this->assertSame([(string)$partnerUid], $this->compile($partnershipUid)['databaseRow']['partner']);
    }

    /**
     * Saves what the form submits when the editor does not touch the field: the chosen
     * entry, or the first entry of the select when none is chosen.
     */
    #[Test]
    #[DataProvider('partnershipsWithAPartnerThatIsNotVisible')]
    public function anUnchangedSaveKeepsAPartnerThatIsNotVisible(int $partnershipUid, int $partnerUid): void
    {
        $result = $this->compile($partnershipUid);
        $submitted = (string)($result['databaseRow']['partner'][0]
            ?? $result['processedTca']['columns']['partner']['config']['items'][0]['value']);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_academicpartners_domain_model_partnership' => [
                    $partnershipUid => ['partner' => $submitted, 'role' => 1],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame($partnerUid, $this->storedPartner($partnershipUid));
    }

    /**
     * The labels of the partner select, without the field's own placeholder entry, in
     * the order the select offers them.
     *
     * @return list<string>
     */
    private function offeredPartnerLabels(): array
    {
        $labels = array_map(
            static fn(array $item): string => (string)($item['label'] ?? ''),
            $this->compile(1)['processedTca']['columns']['partner']['config']['items'] ?? [],
        );

        return array_values(array_filter($labels, static fn(string $label): bool => $label !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $partnershipUid): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academicpartners_domain_model_partnership',
                'vanillaUid' => $partnershipUid,
                'command' => 'edit',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // container service.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
    }

    private function storedPartner(int $partnershipUid): int
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpartners_domain_model_partnership')
            ->select(['partner'], 'tx_academicpartners_domain_model_partnership', ['uid' => $partnershipUid])
            ->fetchAssociative();
        $this->assertIsArray($row);

        return (int)$row['partner'];
    }
}
