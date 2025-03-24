<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model;

use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Partner extends AbstractEntity implements GetCategoryCollectionInterface
{
    protected int $doktype = 0;
    protected string $title = '';
    protected string $abstract = '';
    protected string $description = '';
    protected string $addressStreet = '';
    protected string $addressStreetNumber = '';
    protected string $addressAdditional = '';
    protected string $addressZip = '';
    protected string $addressCity = '';
    protected string $addressCountry = '';
    protected float $geocodeLongitude = 0;
    protected float $geocodeLatitude = 0;
    protected ?\DateTime $geocodeLastRun = null;
    protected string $geocodeStatus = 'open';
    protected string $geocodeMessage = '';
    protected bool $showOnMap = true;
    protected ?CategoryCollection $attributes = null;

    /** @var ObjectStorage<FileReference> */
    protected $media;

    /**
     * @return int<0, max>|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @return int<0, max>|null
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    public function getDoktype(): int
    {
        return $this->doktype;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAddressStreet(): string
    {
        return $this->addressStreet;
    }

    public function getAddressStreetNumber(): string
    {
        return $this->addressStreetNumber;
    }

    public function getAddressAdditional(): string
    {
        return $this->addressAdditional;
    }

    public function getAddressZip(): string
    {
        return $this->addressZip;
    }

    public function getAddressCity(): string
    {
        return $this->addressCity;
    }

    public function getAddressCountry(): string
    {
        return $this->addressCountry;
    }

    public function getAddressCountryLocalizedNameLabel(): string
    {
        if ($this->addressCountry) {
            $country = GeneralUtility::makeInstance(CountryProvider::class)->getByIsoCode($this->addressCountry);
            return $country ? $country->getLocalizedNameLabel() : '';
        }
        return '';
    }

    public function setGeocodeLongitude(float $geocodeLongitude): void
    {
        $this->geocodeLongitude = $geocodeLongitude;
    }

    public function getGeocodeLongitude(): float
    {
        return $this->geocodeLongitude;
    }

    public function getGeocodeLatitude(): float
    {
        return $this->geocodeLatitude;
    }

    public function setGeocodeLatitude(float $latitude): void
    {
        $this->geocodeLatitude = $latitude;
    }

    public function setGeocodeLastRun(\DateTime $geocodeLastRun): void
    {
        $this->geocodeLastRun = $geocodeLastRun;
    }

    public function getGeocodeLastRun(): ?\DateTime
    {
        return $this->geocodeLastRun;
    }

    public function getGeocodeStatus(): string
    {
        return $this->geocodeStatus;
    }

    public function setGeocodeStatus(string $geocodeStatus): void
    {
        $this->geocodeStatus = $geocodeStatus;
    }

    public function getGeocodeMessage(): string
    {
        return $this->geocodeMessage;
    }

    public function setGeocodeMessage(string $geocodeMessage): void
    {
        $this->geocodeMessage = $geocodeMessage;
    }

    public function getShowOnMap(): bool
    {
        return $this->showOnMap;
    }

    public function getAttributes(): CategoryCollection
    {
        return $this->attributes ??= GeneralUtility::makeInstance(CategoryRepository::class)
            ->findByGroupAndPageId('partners', $this->getUid());
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getMedia(): ObjectStorage
    {
        return $this->media;
    }

    public function getCategoryCollection(): CategoryCollection
    {
        return $this->getAttributes();
    }
}
