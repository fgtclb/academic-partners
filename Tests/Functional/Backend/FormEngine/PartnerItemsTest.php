<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPartners\Backend\FormEngine\PartnerItems;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Calls the item handler of the partner field directly, with no global request at all.
 *
 * Below a backend request `Typo3QuerySettings::__construct()` switches
 * `ignoreEnableFields` on by itself on TYPO3 v13, and on TYPO3 v12 only behind the
 * deprecated Extbase feature toggle `ignoreAllEnableFieldsInBe`. With no request it
 * stays off on both, as it does in a frontend request, so only the handler's own
 * setting can make hidden and timed partners appear, whatever the core version and its
 * configuration. `PartnerSelectVisibilityTest` compiles the form for the same partners.
 *
 * The fixture holds one partner page of each kind: visible (10), hidden (11), with a
 * start time in the future (12), with an end time in the past (13) and deleted (14).
 */
final class PartnerItemsTest extends AbstractAcademicPartnersTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerItems/partners.csv');
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * The visible, hidden and timed partners, and not the deleted one.
     */
    #[Test]
    public function everyPartnerThatIsNotDeletedIsOfferedWithoutARequest(): void
    {
        $this->assertSame([10, 11, 12, 13], $this->offeredPartnerUids());
    }

    /**
     * @return list<int>
     */
    private function offeredPartnerUids(): array
    {
        $parameters = ['items' => []];
        GeneralUtility::makeInstance(PartnerItems::class)->itemsProcFunc($parameters);

        $uids = array_map(static fn(array $item): int => (int)$item['value'], $parameters['items']);
        sort($uids);

        return $uids;
    }
}
