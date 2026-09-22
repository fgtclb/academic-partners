<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;

/**
 * Dispatched in {@see PartnerController::listAction()} and {@see PartnerController::mapAction()}
 * after the demand has been built from the content element settings and the request, and
 * before the partners are queried. The demand a listener hands back is the one that is
 * queried and assigned to the view.
 *
 * The map dispatches this event before it restricts the demand to partners that can be
 * drawn, so a listener cannot bring partners without coordinates back onto the map
 * (ACE-562).
 */
final class ModifyPartnerDemandEvent
{
    public function __construct(
        private PartnerDemand $demand,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    public function getDemand(): PartnerDemand
    {
        return $this->demand;
    }

    public function setDemand(PartnerDemand $demand): void
    {
        $this->demand = $demand;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
