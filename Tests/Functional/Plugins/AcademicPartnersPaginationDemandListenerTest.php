<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * A listener of the demand event that hands back a demand it built itself - carrying
 * nothing of the one it was handed, the page included.
 *
 * The page and the arguments of the page links are read from the demand the request
 * asked for, before the event, as the filter redirect reads its URL: the URL carries the
 * visitor's selection, and a listener acts on every request that follows it.
 */
final class AcademicPartnersPaginationDemandListenerTest extends AbstractAcademicPartnersPaginationTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtensionsToLoad('tests/test-partner-list-events');
        parent::setUp();
    }

    protected function additionalSetupFiles(): array
    {
        return ['EXT:test_partner_list_events/Configuration/TypoScript/FreshDemand.typoscript'];
    }

    #[Test]
    public function aReplacedDemandKeepsTheRequestedPage(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?tx_academicpartners_list%5Bdemand%5D%5BsortingField%5D=title'
            . '&tx_academicpartners_list%5Bdemand%5D%5BsortingDirection%5D=asc'
            . '&tx_academicpartners_list%5Bdemand%5D%5BcurrentPage%5D=2'
        );

        $this->assertSame(['Charlie College', 'Delta Academy'], $this->renderedPartners($content));
    }

    /**
     * The fresh demand drops the region, so the list shows every partner - and the links
     * still carry the region the visitor asked for, which the listener drops again on the
     * next page.
     */
    #[Test]
    public function thePageLinksCarryTheRequestedSelection(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/home?tx_academicpartners_list%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=6'
            . '&tx_academicpartners_list%5Bdemand%5D%5BsortingField%5D=title'
            . '&tx_academicpartners_list%5Bdemand%5D%5BsortingDirection%5D=desc'
        );

        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($content));
        $demand = $this->demandArguments($this->paginationLink($content, '2'));
        ksort($demand);
        $this->assertSame(
            [
                'currentPage' => '2',
                'filterCollection' => ['categories' => '6'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $demand,
        );
    }
}
