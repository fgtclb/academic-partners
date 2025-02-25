<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

$iconList['academic-partners'] = [
    'provider' => SvgIconProvider::class,
    'source' => 'EXT:academic_partners/Resources/Public/Icons/Extension.svg',
];

return $iconList;
