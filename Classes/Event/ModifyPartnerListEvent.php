<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPartners\Controller\PartnerController;
use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidViewInterface;

/**
 * Dispatched in {@see PartnerController::listAction()} and {@see PartnerController::mapAction()}
 * after the query and before the view variables are assigned. A listener replaces the
 * partners, replaces the applicable categories, or assigns variables of its own to the
 * view.
 *
 * The categories are the ones computed from the queried partners. They are not recomputed
 * after the event, so a listener that replaces the partners and wants the filter to match
 * them sets the categories as well.
 */
final class ModifyPartnerListEvent
{
    /**
     * @param QueryResultInterface<int, Partner> $partners
     */
    public function __construct(
        private QueryResultInterface $partners,
        private CategoryCollection $categories,
        private readonly PartnerDemand $demand,
        private readonly FluidViewInterface|CoreViewInterface $view,
        private readonly PluginControllerActionContextInterface $pluginControllerActionContext,
    ) {}

    /**
     * @return QueryResultInterface<int, Partner>
     */
    public function getPartners(): QueryResultInterface
    {
        return $this->partners;
    }

    /**
     * @param QueryResultInterface<int, Partner> $partners
     */
    public function setPartners(QueryResultInterface $partners): void
    {
        $this->partners = $partners;
    }

    public function getCategories(): CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getDemand(): PartnerDemand
    {
        return $this->demand;
    }

    public function getView(): FluidViewInterface|CoreViewInterface
    {
        return $this->view;
    }

    public function getPluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
