<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyPartnerDemandEventTest extends UnitTestCase
{
    #[Test]
    public function getDemandReturnsInstanceSetInConstructor(): void
    {
        $demand = new PartnerDemand();
        $event = new ModifyPartnerDemandEvent($demand, $this->createStub(PluginControllerActionContextInterface::class));
        $this->assertSame($demand, $event->getDemand());
    }

    #[Test]
    public function getDemandReturnsTheDemandAListenerReplacedItWith(): void
    {
        $replacement = new PartnerDemand();
        $event = new ModifyPartnerDemandEvent(
            new PartnerDemand(),
            $this->createStub(PluginControllerActionContextInterface::class),
        );
        $event->setDemand($replacement);
        $this->assertSame($replacement, $event->getDemand());
    }

    #[Test]
    public function getPluginControllerActionContextReturnsInstanceSetInConstructor(): void
    {
        $context = $this->createStub(PluginControllerActionContextInterface::class);
        $event = new ModifyPartnerDemandEvent(new PartnerDemand(), $context);
        $this->assertSame($context, $event->getPluginControllerActionContext());
    }
}
