<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Command;

use FGTCLB\AcademicPartners\Command\GeocodeCommand;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Service\GeocodeWriteContext;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The functional `GeocodeCommandTest` runs the command against partner pages of the
 * database, which always carry a uid. What it cannot reach is a partner without one,
 * which the repository never returns but the model allows since its uid is nullable.
 */
final class GeocodeCommandTest extends UnitTestCase
{
    /**
     * The result is written to the page of the partner, so a partner without a uid could
     * not take it. The run fails before Nominatim is asked, rather than spending a request
     * on a result that cannot be stored.
     */
    #[Test]
    public function aPartnerWithoutUidIsRefusedBeforeTheRequest(): void
    {
        $partnerRepository = $this->createStub(PartnerRepository::class);
        $partnerRepository->method('findNextForGeolocation')->willReturn(new Partner());
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects($this->never())->method('request');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Refusing to geocode a partner without a uid.');

        $tester = new CommandTester(new GeocodeCommand(
            $partnerRepository,
            new GeocodeWriteContext($this->createStub(LanguageServiceFactory::class)),
            $requestFactory,
            $logger,
        ));
        $tester->execute(['referrer' => 'https://www.acme.com/']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Refusing to geocode a partner without a uid.', $tester->getDisplay());
    }
}
