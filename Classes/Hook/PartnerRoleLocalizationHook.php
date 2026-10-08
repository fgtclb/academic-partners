<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Hook;

use FGTCLB\AcademicBase\DataHandling\SecondaryParentLocalizationGuard;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Keeps the localization of a partner role from localizing the partnerships it lists
 * (ACE-874). A partnership is translated with its partner page and keeps the role of
 * its default language record.
 *
 * See {@see SecondaryParentLocalizationGuard}. Registered in `ext_localconf.php` as a
 * `processCmdmapClass`, public in `Services.yaml` and stateless.
 */
final class PartnerRoleLocalizationHook
{
    public function __construct(
        private readonly SecondaryParentLocalizationGuard $secondaryParentLocalizationGuard,
    ) {}

    public function processCmdmap_afterFinish(DataHandler $dataHandler): void
    {
        $this->secondaryParentLocalizationGuard->removeChildrenLocalizedWithParent(
            $dataHandler,
            'tx_academicpartners_domain_model_role',
            'partnerships',
        );
    }
}
