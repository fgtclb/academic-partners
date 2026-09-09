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
 * A place does not move when the page is translated, so `geocode_latitude` and
 * `geocode_longitude` are declared `allowLanguageSynchronization` (ACE-562).
 *
 * The upgrade wizard repairs what is already stored; this is the other half, and the
 * half that keeps the defect from coming back: a translation made from now on follows
 * its default record instead of carrying a coordinate of its own. Without the TCA
 * declaration a partner translated before geocoding ran would again reach the map with
 * an empty coordinate and be drawn at 0/0.
 */
final class PartnerCoordinateSynchronizationTest extends AbstractAcademicPartnersTestCase
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerCoordinateSynchronization/partners.csv');
        // DataHandler refuses to localize into a language the site does not offer.
        $this->writeSiteConfiguration(
            identifier: 'partner-coordinate-synchronization',
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
    public function aNewTranslationInheritsTheCoordinatesOfItsDefaultRecord(): void
    {
        $translationUid = $this->localizePartner(10);

        $this->assertSame(['48.137154', '11.576124'], $this->coordinatesOf($translationUid));
    }

    /**
     * The point of synchronization rather than a one-off copy: geocoding usually runs
     * after the page exists, and often after it has been translated.
     */
    #[Test]
    public function geocodingTheDefaultRecordLaterUpdatesTheTranslation(): void
    {
        $this->connection()->update(
            'pages',
            ['geocode_latitude' => null, 'geocode_longitude' => null, 'geocode_status' => 'open'],
            ['uid' => 10],
        );
        $translationUid = $this->localizePartner(10);
        // Whether that lands as NULL or as an empty string is the DataHandler's
        // business; what matters is that there is nothing to draw yet.
        $this->assertNotSame('52.520008', $this->coordinatesOf($translationUid)[0]);

        $this->dataHandler->start(
            [
                'pages' => [
                    10 => [
                        'geocode_latitude' => '52.520008',
                        'geocode_longitude' => '13.404954',
                        'geocode_status' => 'successful',
                    ],
                ],
            ],
            [],
        );
        $this->dataHandler->process_datamap();
        $this->assertSame([], $this->dataHandler->errorLog);

        $this->assertSame(['52.520008', '13.404954'], $this->coordinatesOf($translationUid));
    }

    private function localizePartner(int $uid): int
    {
        $this->dataHandler->start([], ['pages' => [$uid => ['localize' => 1]]]);
        $this->dataHandler->process_cmdmap();
        $this->assertSame([], $this->dataHandler->errorLog);

        return (int)$this->dataHandler->copyMappingArray_merged['pages'][$uid];
    }

    private function connection(): Connection
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable('pages');
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function coordinatesOf(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select('geocode_latitude', 'geocode_longitude')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        return [$row['geocode_latitude'] ?? null, $row['geocode_longitude'] ?? null];
    }
}
