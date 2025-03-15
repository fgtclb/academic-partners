<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace FGTCLB\AcademicPartners\Country;

final class CountryFilter
{
    /**
     * @param array<string> $excludeCountries
     * @param array<string> $onlyCountries
     */
    public function __construct(
        protected array $excludeCountries = [],
        protected array $onlyCountries = []
    ) {}

    /**
     * @return array<string>
     */
    public function getExcludeCountries(): array
    {
        return array_map(strtoupper(...), $this->excludeCountries);
    }

    /**
     * @param array<string> $excludeCountries
     */
    public function setExcludeCountries(array $excludeCountries): CountryFilter
    {
        $this->excludeCountries = $excludeCountries;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getOnlyCountries(): array
    {
        return array_map(strtoupper(...), $this->onlyCountries);
    }

    /**
     * @param array<string> $onlyCountries
     */
    public function setOnlyCountries(array $onlyCountries): CountryFilter
    {
        $this->onlyCountries = $onlyCountries;
        return $this;
    }
}
