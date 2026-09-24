<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Factory;

use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The page of a paginated partner list travels in the demand, `demand[currentPage]`, and
 * arrives as the raw request value: whatever a visitor or a crawler puts into the URL.
 */
final class DemandFactoryCurrentPageTest extends AbstractAcademicPartnersTestCase
{
    /**
     * @return \Generator<string, array{0: array<string, mixed>|null, 1: int}>
     */
    public static function currentPageDataProvider(): \Generator
    {
        yield 'no demand at all' => [null, 1];
        yield 'a demand without a page' => [['sortingField' => 'title'], 1];
        yield 'a valid page' => [['currentPage' => '3'], 3];
        yield 'a valid page next to a sorting' => [['sortingField' => 'title', 'currentPage' => '2'], 2];
        yield 'page zero' => [['currentPage' => '0'], 1];
        yield 'a negative page' => [['currentPage' => '-2'], 1];
        yield 'not a number' => [['currentPage' => 'last'], 1];
        yield 'an array' => [['currentPage' => ['2']], 1];
    }

    /**
     * @param array<string, mixed>|null $demandFromRequest
     */
    #[Test]
    #[DataProvider('currentPageDataProvider')]
    public function currentPageIsReadFromTheDemand(?array $demandFromRequest, int $expectedPage): void
    {
        $demand = $this->get(DemandFactory::class)->createDemandObject($demandFromRequest, [], ['uid' => 1]);

        $this->assertSame($expectedPage, $demand->getCurrentPage());
    }

    /**
     * The redirect of a filter submission is built from these arguments. Without a page in
     * them, a visitor who changes the filter on page two starts on page one of the new
     * selection - which may not even have a page two.
     */
    #[Test]
    public function theArgumentsOfAFilterRedirectCarryNoPage(): void
    {
        $factory = $this->get(DemandFactory::class);
        $demand = $factory->createDemandObject(['sortingField' => 'title', 'currentPage' => '3'], [], ['uid' => 1]);

        $this->assertArrayNotHasKey('currentPage', $factory->createDemandArguments($demand));
    }
}
