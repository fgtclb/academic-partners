<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Task;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class GeocodeTask extends AbstractTask
{
    /**
     * Base URL to fetch latitude and longitude of a partner string
     */
    protected string $geocodingUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=5';

    /**
     * Valid HTTP referer required by Nominatim's usage policy
     */
    protected string $geocodingReferer = 'https://www.th-bingen.de/';

    public function execute(): bool
    {
        $partnerRepository = GeneralUtility::makeInstance(PartnerRepository::class);
        $partner = $partnerRepository->findNextForGeolocation();

        if (!$partner) {
            return true;
        }

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $now = new \DateTime();
        $partner->setGeocodeLastRun($now);

        $address = [];
        $address['street'] = implode(' ', [$partner->getAddressStreet(), $partner->getAddressStreetNumber()]);
        $address['city'] = $partner->getAddressCity();
        $address['postalcode'] = $partner->getAddressZip();
        $address['country'] = $partner->getAddressCountry();
        $addressQuery = HttpUtility::buildQueryString($address, '&', true);

        if ($addressQuery === '') {
            $partner->setGeocodeStatus('failed');
            $partner->setGeocodeMessage('There are no address details given.');
            $partnerRepository->update($partner);
            $persistenceManager->persistAll();
            return true;
        }

        $url = $this->geocodingUrl . $addressQuery;

        $additionalOptions = [
            'headers' => [
                'Referer' => $this->geocodingReferer,
            ],
        ];

        try {
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            $response = $requestFactory->request($url, 'GET', $additionalOptions);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning(
                    'Request to Nominatim API returned status "{statusCode}".',
                    [
                        'request' => $url,
                        'statusCode' => $e->getCode(),
                        'errorMessage' => $e->getMessage(),
                    ]
                );
            }
            return true;
        }

        $content = $response->getBody()->getContents();
        $geodata = json_decode($content, true);

        if (is_array($geodata[0]) && !empty($geodata[0])) {
            $partner->setGeocodeLatitude((float)($geodata[0]['lat']));
            $partner->setGeocodeLongitude((float)($geodata[0]['lon']));
            $partner->setGeocodeStatus('successful');
        } else {
            $partner->setGeocodeStatus('failed');
            $partner->setGeocodeMessage('The address details were not sufficient for geolocalization.');
        }

        $partnerRepository->update($partner);
        $persistenceManager->persistAll();

        return true;
    }
}
