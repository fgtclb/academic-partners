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
    protected ObjectStorage $media;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
        $this->media = new ObjectStorage();
    }

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

    public function getAbstract(): string
    {
        return $this->abstract;
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

    /**
     * Whether this partner has a coordinate that can be drawn on a map.
     *
     * This is the model end of the rule `PartnerRepository::drawableCoordinatesConstraint()`
     * applies to the map query and `Resources/Private/TypeScript/frontend/map.ts` applies to
     * the markup: the pair `0/0` means "nothing was written", while a single zero is a real
     * coordinate - longitude 0 runs through the United Kingdom, France, Spain and Ghana, and
     * latitude 0 is the equator. Like the module, it also refuses a coordinate that is not a
     * finite number.
     *
     * It exists for templates that render one partner rather than a query result. A detail
     * page drawing the partner's own location has no query to constrain, so without this it
     * renders an empty map centred on Germany - and before ACE-562 taught the module to refuse
     * the pair, a marker at 0/0 off the coast of Africa.
     *
     * Deliberately not called "geo located", for the same reason as
     * `PartnerDemand::setDrawableOnly()`: `PartnerRepository::findGeoLocated()` also requires
     * a geocode *status*, and a status is a claim rather than a coordinate. A record can be
     * `manually` located and carry no coordinate at all.
     *
     * The model does not see what the query sees, in either direction. Extbase's `DataMapper`
     * skips a `NULL` column, which leaves the property at its default of `0`, and casts the
     * empty string to `0.0` - so absence cannot be told apart from a stored zero, and a pair
     * with only one coordinate missing reads as that coordinate being 0 and is reported
     * drawable where the map query excludes it. The other way round, a hand-entered `0.0` or
     * `-0` becomes `0.0` here and is refused, as the module refuses it, while the string-based
     * query lets it through. ACE-566, which changes the columns to a numeric type, is where
     * the two are reconciled.
     */
    public function isDrawable(): bool
    {
        if (!is_finite($this->geocodeLatitude) || !is_finite($this->geocodeLongitude)) {
            return false;
        }

        return !($this->geocodeLatitude === 0.0 && $this->geocodeLongitude === 0.0);
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
