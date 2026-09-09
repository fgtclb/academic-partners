<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model\Dto;

use FGTCLB\AcademicPartners\Enumeration\SortingOptions;
use FGTCLB\CategoryTypes\Collection\FilterCollection;

class PartnerDemand
{
    /** @var int[] */
    protected array $pages = [];
    protected ?FilterCollection $filterCollection = null;
    protected bool $showHiddenRecords = false;
    protected bool $drawableOnly = false;
    protected string $sorting = '';
    protected string $sortingField = '';
    protected string $sortingDirection = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void
    {
        $this->setSorting(SortingOptions::__default);
    }

    /**
     * @param int[] $pages
     */
    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return int[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    public function getFilterCollection(): ?FilterCollection
    {
        return $this->filterCollection;
    }

    public function setFilterCollection(?FilterCollection $filterCollection): void
    {
        $this->filterCollection = $filterCollection;
    }

    public function setShowHiddenRecords(bool $showHiddenRecords): void
    {
        $this->showHiddenRecords = $showHiddenRecords;
    }

    public function getShowHiddenRecords(): bool
    {
        return $this->showHiddenRecords;
    }

    /**
     * Restricts the result to partners that can actually be drawn on a map. Set by the
     * map action; the list leaves it off, because a partner without coordinates is
     * still a perfectly good list entry.
     *
     * Deliberately not called "geo located": `PartnerRepository::findGeoLocated()`
     * means a geocode *status* as well, while this asks only whether there is a
     * coordinate to draw.
     *
     * `Partner::isDrawable()` is the same rule for a template that renders one partner
     * instead of a query result, such as a detail page drawing the partner's own place.
     */
    public function setDrawableOnly(bool $drawableOnly): void
    {
        $this->drawableOnly = $drawableOnly;
    }

    public function getDrawableOnly(): bool
    {
        return $this->drawableOnly;
    }

    public function setSorting(string $sorting): void
    {
        if (in_array($sorting, SortingOptions::getConstants())) {
            $this->sorting = $sorting;
            [$this->sortingField, $this->sortingDirection] = explode(' ', $sorting);
        }
    }

    public function getSorting(): string
    {
        return $this->sorting;
    }

    public function setSortingField(string $sortingField): void
    {
        $newSorting = $sortingField . ' ' . $this->sortingDirection;
        $this->setSorting($newSorting);
    }

    public function getSortingField(): string
    {
        return $this->sortingField;
    }

    public function setSortingDirection(string $sortingDirection): void
    {
        $newSorting = $this->sortingField . ' ' . $sortingDirection;
        $this->setSorting($newSorting);
    }

    public function getSortingDirection(): string
    {
        return $this->sortingDirection;
    }
}
