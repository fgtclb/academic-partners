<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
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
 * Compiles the backend form of a partnership record the way FormEngine does when an
 * editor opens it, and asserts the order of the partner select.
 *
 * The order has to be asserted here and not on `PartnerItems::itemsProcFunc()`: core
 * applies the order a select declares **after** the item handler has produced the
 * entries - on TYPO3 v12 and v13 alike, as the last step of
 * `TcaSelectItems::addData()`. The handler's own output is unordered before and after
 * this change, so a test calling it directly passes either way and proves nothing.
 *
 * The fixture titles are anti-correlated with both orders the select could otherwise
 * follow, and the three orders differ in their first entry:
 *
 * - by `uid`:     Zeta, Oberlin, Öresund, Beta, Potsdam, Alpha
 * - by `sorting`: Zeta, Öresund, Beta, Potsdam, Alpha, Oberlin  (the page tree order,
 *                 which `PartnerRepository::findAll()` asks for since ACE-491)
 * - by title:     Alpha, Beta, Oberlin, Öresund, Potsdam, Zeta
 */
final class PartnerSelectOrderTest extends AbstractAcademicPartnersTestCase
{
    private const PARTNERSHIP_UID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerSelectOrder/partners.csv');
        $this->setUpBackendUser(1);
        // The order is produced by an ICU collator bound to the backend language, so the
        // expectations below only hold for a known one.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * The hidden partner is offered along with the others on TYPO3 v12 and v13, so it
     * takes its place in the alphabetical order rather than being absent from it.
     */
    #[Test]
    public function partnersAreOfferedInAlphabeticalOrder(): void
    {
        $this->assertSame(
            [
                'Alpha Academy',
                'Beta Hidden Partner',
                'Oberlin Institute',
                'Öresund Academy',
                'Potsdam College',
                'Zeta University',
            ],
            $this->offeredPartnerLabels(),
        );
    }

    /**
     * The collator orders a diacritic by its base letter, which is the reason the order
     * is declared on the field rather than sorted in PHP: both `<=>` and
     * `asort(SORT_LOCALE_STRING)` - the two comparisons the sibling selects of this mono
     * repository use - place `Ö` after `Z`, because its UTF-8 encoding starts above the
     * ASCII letters.
     */
    #[Test]
    public function aTitleStartingWithADiacriticIsOrderedByItsBaseLetter(): void
    {
        $labels = $this->offeredPartnerLabels();

        $this->assertSame(
            ['Oberlin Institute', 'Öresund Academy', 'Potsdam College'],
            array_slice($labels, (int)array_search('Oberlin Institute', $labels, true), 3),
        );
    }

    /**
     * The placeholder is the field's own item and is sorted along with the partners. Its
     * label is empty, which every non-empty label collates after, so it stays first -
     * pinned here rather than left to that argument.
     */
    #[Test]
    public function thePlaceholderEntryStaysTheFirstEntry(): void
    {
        $items = $this->partnerItems();

        $this->assertSame('', $items[0]['label']);
        $this->assertSame(0, $items[0]['value']);
    }

    /**
     * Deleted partner pages are not offered on either core version, and ordering the
     * select does not change that. The soft delete clause is applied by
     * `Typo3DbQueryParser` independently of the enable fields.
     */
    #[Test]
    public function deletedPartnersAreNotOffered(): void
    {
        $this->assertNotContains('Gamma Deleted Partner', $this->offeredPartnerLabels());
    }

    /**
     * Hidden partner pages are offered on TYPO3 v12 and v13, and the assertion above
     * pins them in their alphabetical position rather than out of the list.
     *
     * That is a decision of this extension (ACE-672): the select offers every partner
     * page that is not deleted, and `PartnerItems::itemsProcFunc()` says so itself
     * rather than relying on the backend default of `Typo3QuerySettings`, which v12
     * applies only behind the deprecated Extbase feature toggle
     * `ignoreAllEnableFieldsInBe`. `PartnerSelectVisibilityTest` covers partners
     * outside their start or end time and a partnership whose partner is not visible,
     * and `PartnerItemsTest` covers the handler without a backend request.
     */
    #[Test]
    public function hiddenPartnersAreOffered(): void
    {
        $this->assertContains('Beta Hidden Partner', $this->offeredPartnerLabels());
    }

    /**
     * The order is a property of the rendered select only. Saving a record without
     * touching the field keeps the partner it refers to, so no stored value follows the
     * new order.
     */
    #[Test]
    public function anUnchangedSaveKeepsTheStoredPartner(): void
    {
        $this->assertSame(13, $this->storedPartner());

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'tx_academicpartners_domain_model_partnership' => [
                    self::PARTNERSHIP_UID => ['role' => 1],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(13, $this->storedPartner());
    }

    /**
     * The labels of the partner select, without the field's own placeholder entry.
     *
     * @return list<string>
     */
    private function offeredPartnerLabels(): array
    {
        $labels = array_map(
            static fn(array $item): string => (string)($item['label'] ?? ''),
            $this->partnerItems(),
        );

        return array_values(array_filter($labels, static fn(string $label): bool => $label !== ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function partnerItems(): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academicpartners_domain_model_partnership',
                'vanillaUid' => self::PARTNERSHIP_UID,
                'command' => 'edit',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // container service, and asking the test container for it raises
            // `ServiceNotFoundException`.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );

        return array_values($result['processedTca']['columns']['partner']['config']['items'] ?? []);
    }

    private function storedPartner(): int
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpartners_domain_model_partnership')
            ->select(['partner'], 'tx_academicpartners_domain_model_partnership', ['uid' => self::PARTNERSHIP_UID])
            ->fetchAssociative();
        $this->assertIsArray($row);

        return (int)$row['partner'];
    }
}
