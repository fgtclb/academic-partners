<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Command;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Service\GeocodeWriteContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Geocode one partner address per execution run where geocoding is missing.
 * @todo Extract geocoding part into dedicated geocoding service and handling
 *       partner geocoding either in a intermediate service or the repository.
 */
#[AsCommand(
    name: 'academic:geocodepartners',
    description: 'Geocode partner addresses using the Nominatim API.',
)]
final class GeocodeCommand extends Command
{
    // Base URL to fetch latitude and longitude of a partner string
    protected string $geocodingUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=5';

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly GeocodeWriteContext $geocodeWriteContext,
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Geocode partner addresses using the Nominatim API')
            ->addArgument(
                'referrer',
                InputArgument::REQUIRED,
                'HTTP referer to identify app in Nominatim\'s API',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $referrer = $input->getArgument('referrer');
        $partner = $this->partnerRepository->findNextForGeolocation();

        // Nothing to geocode
        if (!$partner) {
            return Command::SUCCESS;
        }

        if ($partner->getAddressStreet() === ''
            || $partner->getAddressStreetNumber() === ''
            || $partner->getAddressCity() === ''
            || $partner->getAddressZip() === ''
            || $partner->getAddressCountry() === ''
        ) {
            return $this->writeGeocodeResult($partner->getUid(), [
                'geocode_status' => 'failed',
                'geocode_message' => 'There are not sufficient address details given.',
            ]) ? Command::SUCCESS : Command::FAILURE;
        }

        $now = new \DateTime();

        $address = [];
        $address['street'] = trim(implode(' ', [$partner->getAddressStreet(), $partner->getAddressStreetNumber()]));
        $address['city'] = $partner->getAddressCity();
        $address['postalcode'] = $partner->getAddressZip();
        $address['country'] = $partner->getAddressCountry();
        $addressQuery = HttpUtility::buildQueryString($address, '&', true);

        $url = $this->geocodingUrl . $addressQuery;

        // Add valid HTTP referer to identify app as required by Nominatim's usage policy
        $additionalOptions = [
            'headers' => [
                'Referrer' => $referrer,
            ],
        ];

        try {
            $response = $this->requestFactory->request($url, 'GET', $additionalOptions);
        } catch (\Exception $e) {
            $this->logger->warning(
                'Request to Nominatim API returned status "{statusCode}".',
                [
                    'request' => $url,
                    'statusCode' => $e->getCode(),
                    'errorMessage' => $e->getMessage(),
                ]
            );
            return Command::FAILURE;
        }

        $content = $response->getBody()->getContents();

        try {
            $geodata = \json_decode(json: $content, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning(
                'Failed to json decode Nominatim response content: ' . $e->getMessage(),
                [
                    'content' => $content,
                    'exception' => $e,
                ]
            );
            return Command::FAILURE;
        }

        $values = ['geocode_last_run' => $now->getTimestamp()];
        if (isset($geodata[0]) && !empty($geodata[0])) {
            $values['geocode_latitude'] = (string)(float)$geodata[0]['lat'];
            $values['geocode_longitude'] = (string)(float)$geodata[0]['lon'];
            $values['geocode_status'] = 'successful';
        } else {
            $values['geocode_status'] = 'failed';
            $values['geocode_message'] = 'The address details were not sufficient for geolocalization.';
        }

        return $this->writeGeocodeResult($partner->getUid(), $values)
            ? Command::SUCCESS
            : Command::FAILURE;
    }

    /**
     * Written through the DataHandler rather than the repository on purpose. The
     * coordinate columns are `allowLanguageSynchronization`, and only a DataHandler
     * write runs `DataMapProcessor`: persisting through Extbase reaches the default
     * record and leaves every translation carrying whatever it carried before, which
     * is what kept translated partners off the map (ACE-562).
     *
     * A refused write has to fail the run. `findNextForGeolocation()` selects on
     * `geocode_status = 'open'`, so a result that never reaches the database leaves the
     * status untouched and the next scheduled run picks the same partner again - an
     * unbounded retry against Nominatim, which is what the mandatory referrer argument
     * exists to keep legitimate. The DataHandler refuses silently where the previous
     * `persistAll()` threw, so the error log is what has to be checked.
     *
     * @param array<string, string|int> $values
     */
    private function writeGeocodeResult(?int $partnerUid, array $values): bool
    {
        if ($partnerUid === null) {
            $this->logger->error('Refusing to geocode a partner without a uid.');

            return false;
        }

        $written = false;
        $this->geocodeWriteContext->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($partnerUid, $values, &$written): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start(['pages' => [$partnerUid => $values]], [], $backendUser);
                $dataHandler->process_datamap();

                foreach ($dataHandler->errorLog as $message) {
                    $this->logger->error(
                        'The DataHandler refused a geocoding result: {message}',
                        [
                            'partner' => $partnerUid,
                            'message' => $message,
                        ]
                    );
                }

                $written = $dataHandler->errorLog === [];
            }
        );

        return $written;
    }
}
