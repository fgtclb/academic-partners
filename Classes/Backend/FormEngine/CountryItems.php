<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Backend\FormEngine;

use FGTCLB\AcademicPartners\Country\CountryProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class CountryItems
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        $countryProvider = GeneralUtility::makeInstance(CountryProvider::class);
        $allCountries = $countryProvider->getAll();

        $items = [];
        foreach ($allCountries as $country) {
            $items[$country->getAlpha2IsoCode()] = LocalizationUtility::translate($country->getLocalizedNameLabel());
        }
        asort($items, SORT_LOCALE_STRING);

        foreach ($items as $key => $value) {
            $parameters['items'][] = [
                $value,
                $key,
                'flags-' . strtolower($key),
            ];
        }
    }
}
