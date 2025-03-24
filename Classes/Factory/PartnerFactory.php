<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Factory;

use FGTCLB\AcademicPartners\Domain\Model\Partner as PartnerModel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Factory class for partners
 */
class PartnerFactory
{
    /**
     * @param array<int|string, mixed> $properties page properties of the current page
     * @return PartnerModel
     */
    public function get(array $properties): PartnerModel
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $partnerModels = $dataMapper->map(PartnerModel::class, [$properties]);
        return $partnerModels[0];
    }
}
