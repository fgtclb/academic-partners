<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyPartnerListEventTest extends UnitTestCase
{
    /**
     * @param QueryResultInterface<int, Partner>|null $partners
     */
    private function event(
        ?QueryResultInterface $partners = null,
        ?CategoryCollection $categories = null,
        ?PartnerDemand $demand = null,
        ?ViewInterface $view = null,
        ?PluginControllerActionContextInterface $context = null,
    ): ModifyPartnerListEvent {
        return new ModifyPartnerListEvent(
            $partners ?? $this->createStub(QueryResultInterface::class),
            $categories ?? new CategoryCollection(),
            $demand ?? new PartnerDemand(),
            $view ?? $this->createStub(ViewInterface::class),
            $context ?? $this->createStub(PluginControllerActionContextInterface::class),
        );
    }

    #[Test]
    public function getPartnersReturnsInstanceSetInConstructor(): void
    {
        $partners = $this->createStub(QueryResultInterface::class);
        $this->assertSame($partners, $this->event(partners: $partners)->getPartners());
    }

    #[Test]
    public function getPartnersReturnsTheResultAListenerReplacedItWith(): void
    {
        $replacement = $this->createStub(QueryResultInterface::class);
        $event = $this->event();
        $event->setPartners($replacement);
        $this->assertSame($replacement, $event->getPartners());
    }

    #[Test]
    public function getCategoriesReturnsInstanceSetInConstructor(): void
    {
        $categories = new CategoryCollection();
        $this->assertSame($categories, $this->event(categories: $categories)->getCategories());
    }

    #[Test]
    public function getCategoriesReturnsTheCollectionAListenerReplacedItWith(): void
    {
        $replacement = new CategoryCollection();
        $event = $this->event();
        $event->setCategories($replacement);
        $this->assertSame($replacement, $event->getCategories());
    }

    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new PartnerDemand();
        $this->assertSame($demand, $this->event(demand: $demand)->getDemand());
    }

    #[Test]
    public function getViewReturnsInstanceSetInConstructor(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $this->assertSame($view, $this->event(view: $view)->getView());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $this->assertSame($context, $this->event(context: $context)->getPluginControllerActionContext());
    }
}
