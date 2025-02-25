<?php

declare(strict_types=1);

use FGTCLB\AcademicPartners\Domain\Enumeration\CategoryTypes;

$sourceString = function (string $icon) {
    return sprintf(
        'EXT:academic_partners/Resources/Public/Icons/%s.svg',
        \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($icon)
    );
};

$identifierString = function (string $identifier) {
    return sprintf(
        'academic_partners_%s',
        $identifier
    );
};

/**
 * Don't name this variable $icons, because this would override the default variable,
 * when this file gets required by the default service provider.
 */
$typedCategoryIcons = [];
foreach (CategoryTypes::getConstants() as $constant) {
    $identifier = $identifierString($constant);
    $typedCategoryIcons[$identifier] = [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        'source' => $sourceString($identifier),
    ];
}
return $typedCategoryIcons;
