<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Command;

use FGTCLB\AcademicPartners\Command\GeocodeCommand;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Tester\CommandTester;
use TESTS\TestPartnersStub\Http\StubNominatimHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The command persists through the DataHandler rather than the repository, and this is
 * why: the coordinate columns are `allowLanguageSynchronization`, and only a DataHandler
 * write runs `DataMapProcessor`. Persisting through Extbase reaches the default record
 * and leaves every translation carrying whatever it carried before - which is how a
 * partner translated before geocoding ran stayed off the translated map (ACE-562).
 *
 * Geocoding is the one path that writes coordinates in production, so if it does not
 * synchronize, the fix only ever applies to backend edits and to rows the upgrade
 * wizard happened to catch.
 *
 * No outgoing HTTP: `test_partners_stub` answers every request with a canned Nominatim
 * result.
 */
final class GeocodeCommandTest extends AbstractAcademicPartnersTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        // By composer package name: `sbuerk/fixture-packages` registers the autoload of
        // every fixture extension under `Tests/Functional/Fixtures/Extensions/`.
        $this->testExtensionsToLoad[] = 'tests/test-partners-stub';
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/GeocodeCommand/partners.csv');
        $this->writeSiteConfiguration(
            identifier: 'geocode-command',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: '/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        // Deliberately no `setUpBackendUser()`: the command runs from the CLI, where
        // there is none, and `GeocodeWriteContext` is what supplies one. With an
        // authenticated user in the global, `DataHandler::start()` would fall back to it
        // and the service could be deleted without a test noticing.
        unset($GLOBALS['BE_USER']);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function theGeocodedPartnerReceivesTheCoordinates(): void
    {
        $this->runCommand();

        $this->assertSame(
            [StubNominatimHandler::LATITUDE, StubNominatimHandler::LONGITUDE],
            $this->coordinatesOf(10),
        );
    }

    #[Test]
    public function theTranslationReceivesTheCoordinatesToo(): void
    {
        $this->runCommand();

        $this->assertSame(
            [StubNominatimHandler::LATITUDE, StubNominatimHandler::LONGITUDE],
            $this->coordinatesOf(110),
        );
    }

    #[Test]
    public function theGeocodeStatusIsRecorded(): void
    {
        $this->runCommand();

        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $status = $queryBuilder
            ->select('geocode_status')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(10, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        $this->assertSame('successful', $status);
    }

    private function runCommand(): void
    {
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS, 'the CLI condition this test is about');

        $command = $this->get(GeocodeCommand::class);
        $this->assertInstanceOf(GeocodeCommand::class, $command);

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['referrer' => 'https://www.acme.com/']));
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
