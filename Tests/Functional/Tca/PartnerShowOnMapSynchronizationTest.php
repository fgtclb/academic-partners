<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The partner map reads "Show on map" from the record of the page language, so the column
 * is declared `allowLanguageSynchronization`, as the coordinates are: a translation follows
 * its default record, and an editor can still detach it for one language.
 */
final class PartnerShowOnMapSynchronizationTest extends AbstractAcademicPartnersTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private DataHandler $dataHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerShowOnMapSynchronization/partners.csv');
        // DataHandler refuses to localize into a language the site does not offer.
        $this->writeSiteConfiguration(
            identifier: 'partner-show-on-map-synchronization',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function switchingTheDefaultRecordOffTheMapSwitchesOffANewTranslation(): void
    {
        $this->dataHandler->start([], ['pages' => [10 => ['localize' => 1]]]);
        $this->dataHandler->process_cmdmap();
        $this->assertSame([], $this->dataHandler->errorLog);
        $translationUid = (int)$this->dataHandler->copyMappingArray_merged['pages'][10];
        $this->assertSame(1, $this->showOnMapOf($translationUid));

        $this->switchOffTheMap(10);

        $this->assertSame(0, $this->showOnMapOf($translationUid));
    }

    /**
     * A translation that exists from before the column was synchronized carries no
     * synchronization state for it.
     */
    #[Test]
    public function switchingTheDefaultRecordOffTheMapSwitchesOffAnExistingTranslation(): void
    {
        $this->switchOffTheMap(20);

        $this->assertSame(0, $this->showOnMapOf(120));
    }

    /**
     * A translation that exists from before this change with the synchronization state
     * ACE-562 wrote for the coordinates, and none for the switch.
     */
    #[Test]
    public function switchingTheDefaultRecordOffTheMapSwitchesOffATranslationWithCoordinateStates(): void
    {
        $this->switchOffTheMap(30);

        $this->assertSame(0, $this->showOnMapOf(130));
    }

    /**
     * An editor who detached the switch of a translation decides it for that language.
     */
    #[Test]
    public function aDetachedTranslationKeepsItsSwitch(): void
    {
        $this->switchOffTheMap(40);

        $this->assertSame(1, $this->showOnMapOf(140));
    }

    private function switchOffTheMap(int $uid): void
    {
        $this->dataHandler->start(['pages' => [$uid => ['show_on_map' => 0]]], []);
        $this->dataHandler->process_datamap();
        $this->assertSame([], $this->dataHandler->errorLog);
    }

    private function showOnMapOf(int $uid): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('show_on_map')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }
}
