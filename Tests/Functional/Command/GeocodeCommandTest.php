<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Command;

use FGTCLB\AcademicPartners\Command\GeocodeCommand;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use Symfony\Component\Console\Tester\CommandTester;
use TESTS\TestPartnersStub\Http\StubNominatimHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * The command persists through the DataHandler rather than the repository, and this is
 * why: the coordinate columns are `allowLanguageSynchronization`, and only a DataHandler
 * write runs `DataMapProcessor`. Persisting through Extbase reaches the default record
 * and leaves every translation carrying whatever it carried before - which is how a
 * partner translated before geocoding ran stayed off the translated map (ACE-709).
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

    /**
     * The command's own channel writes everything from info on into a file of its own, so
     * a test can read what a scheduled run, which has no console output, leaves behind.
     */
    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'FGTCLB' => [
                'AcademicPartners' => [
                    'Command' => [
                        'GeocodeCommand' => [
                            'writerConfiguration' => [
                                LogLevel::INFO => [
                                    FileWriter::class => ['logFileInfix' => 'geocodecommand'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
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
        StubNominatimHandler::$requests = [];
        // The test instance is reused across the tests of this class, and so is the log
        // file of the command: start every test with an empty one. Truncated, not
        // deleted, as `FileWriter` keeps its handle open for the whole process and appends.
        foreach (glob($this->instancePath . '/typo3temp/var/log/typo3_geocodecommand_*.log') ?: [] as $logFile) {
            file_put_contents($logFile, '');
        }
        // Deliberately no `setUpBackendUser()`: the command runs from the CLI, where
        // there is none, and `GeocodeWriteContext` is what supplies one. With an
        // authenticated user in the global, `DataHandler::start()` would fall back to it
        // and the service could be deleted without a test noticing.
        unset($GLOBALS['BE_USER']);
    }

    protected function tearDown(): void
    {
        StubNominatimHandler::$requests = [];
        StubNominatimHandler::$failWithStatus = null;
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

    /**
     * Nominatim's usage policy asks for an identification, and the command takes it as
     * its `referrer` argument. The HTTP header is spelled `Referer`, a header named
     * `Referrer` reaches Nominatim as an unknown one.
     */
    #[Test]
    public function theReferrerArgumentIsSentAsRefererHeader(): void
    {
        $this->runCommand();

        $this->assertCount(1, StubNominatimHandler::$requests);
        $request = StubNominatimHandler::$requests[0];
        $this->assertSame(['https://www.acme.com/'], $request->getHeader('Referer'));
        $this->assertFalse($request->hasHeader('Referrer'));
    }

    #[Test]
    public function theRunReportsTheGeocodedPartner(): void
    {
        $this->assertSame(
            'Partner 10 "Alpha University": geocoded to ' . StubNominatimHandler::LATITUDE . ', ' . StubNominatimHandler::LONGITUDE . '.',
            trim($this->runCommand()),
        );
    }

    #[Test]
    public function theRunReportsAnIncompleteAddress(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->update('pages', ['address_city' => ''], ['uid' => 10]);

        $this->assertSame(
            'Partner 10 "Alpha University": geocoding failed. There are not sufficient address details given.',
            trim($this->runCommand()),
        );
        $this->assertSame([], StubNominatimHandler::$requests, 'An incomplete address was sent to Nominatim.');
    }

    #[Test]
    public function theRunReportsAnEmptyQueue(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->update('pages', ['geocode_status' => 'successful'], ['uid' => 10]);

        $this->assertSame('No partner is waiting for geocoding.', trim($this->runCommand()));
        $this->assertSame([], StubNominatimHandler::$requests);
    }

    /**
     * A failed request is an error on stderr and a failed run, and leaves the partner in
     * the queue for the next run.
     */
    #[Test]
    public function aFailedRequestIsReportedAsAnError(): void
    {
        StubNominatimHandler::$failWithStatus = 503;

        $tester = $this->executeCommand(1);

        $this->assertSame('', $tester->getDisplay());
        $this->assertStringContainsString(
            'Partner 10 "Alpha University": the request to Nominatim failed',
            $tester->getErrorOutput(),
        );
        $this->assertSame('open', $this->geocodeStatusOf(10));
    }

    /**
     * The scheduler runs a command with a `NullOutput`, so the log is all a scheduled run
     * leaves. A geocoded partner is info, one that could not be geocoded a warning.
     */
    #[Test]
    public function theResultIsLogged(): void
    {
        $this->runCommand();
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')
            ->update('pages', ['geocode_status' => 'open', 'address_city' => ''], ['uid' => 10]);
        // Two scheduled runs are two processes. Without this the second run gets the
        // partner from the Extbase session of the first, with the old address.
        $this->get(PersistenceManager::class)->clearState();
        $this->runCommand();

        $log = $this->readCommandLog();
        $this->assertMatchesRegularExpression('/\[INFO\].*Partner 10 "Alpha University": geocoded to /', $log);
        $this->assertMatchesRegularExpression('/\[WARNING\].*Partner 10 "Alpha University": geocoding failed\./', $log);
    }

    /**
     * @return string What the command printed.
     */
    private function runCommand(): string
    {
        return $this->executeCommand(0)->getDisplay();
    }

    private function executeCommand(int $expectedExitCode): CommandTester
    {
        $this->assertArrayNotHasKey('BE_USER', $GLOBALS, 'the CLI condition this test is about');

        $command = $this->get(GeocodeCommand::class);
        $this->assertInstanceOf(GeocodeCommand::class, $command);

        $tester = new CommandTester($command);
        $this->assertSame(
            $expectedExitCode,
            $tester->execute(['referrer' => 'https://www.acme.com/'], ['capture_stderr_separately' => true]),
        );

        return $tester;
    }

    private function readCommandLog(): string
    {
        $files = glob($this->instancePath . '/typo3temp/var/log/typo3_geocodecommand_*.log') ?: [];
        $this->assertCount(1, $files, 'The command wrote no log file of its own.');

        return (string)file_get_contents($files[0]);
    }

    private function geocodeStatusOf(int $uid): string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (string)$queryBuilder
            ->select('geocode_status')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
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
