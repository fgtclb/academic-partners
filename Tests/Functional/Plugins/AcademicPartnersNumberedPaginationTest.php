<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;

/**
 * Pagination of the partner list with `georgringer/numbered-pagination` loaded: the page
 * links are limited to `numberOfLinks`, which the fixture sets to 2.
 */
final class AcademicPartnersNumberedPaginationTest extends AbstractAcademicPartnersPaginationTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination');
        parent::setUp();
    }

    #[Test]
    public function thePageLinksAreLimitedToTheNumberOfLinks(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($content));
        $this->assertSame(['1', '2', '…', 'next', 'last'], $this->paginationLabels($content));
    }

    /**
     * Past the limit, the links move with the current page, and the last page is still one
     * link away.
     */
    #[Test]
    public function theLastPageIsReachedThroughTheLimitedLinks(): void
    {
        $lastPage = $this->renderFrontendPage($this->paginationLink($this->renderFrontendPage('https://www.acme.com/home'), 'last'));

        $this->assertSame(['Echo School', 'Foxtrot University'], $this->renderedPartners($lastPage));
        $this->assertSame(['first', 'previous', '…', '2', '3'], $this->paginationLabels($lastPage));
    }
}
