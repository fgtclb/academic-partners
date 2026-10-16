<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Controller;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\AcademicPartners\Security\MapTileSourceRegistration;
use FGTCLB\AcademicPartners\Service\PartnershipRoles;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PartnerController extends ActionController
{
    private FilterTypeResolver $filterTypeResolver;

    private PartnershipRoles $partnershipRoles;

    private MapTileSourceRegistration $mapTileSourceRegistration;

    public function __construct(
        protected PartnerRepository $partnerRepository,
        protected PartnershipRepository $partnershipRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $partnerDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function mapAction(?array $demand = null): ResponseInterface
    {
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        // A partner without coordinates cannot be drawn and would end up at 0/0
        // instead of being left out (ACE-709), and a partner hidden from the map
        // ("Show on map", ACE-770) is left out as well. The list leaves the flag
        // off: such a partner is still a perfectly good list entry.
        $demandObject->setDrawableOnly(true);

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
        ]);
        // The host of the tiles for the policy of the page. TYPO3 v12 builds the policy after
        // the whole page, so this covers every request there. TYPO3 v13 prepares it before
        // this action runs on the request that generates the page, where the content element
        // registers the host, see MapTileSourceProcessor, and this covers the requests served
        // from the cache, also of a site that renders the element without the data
        // processors of the content element.
        $this->mapTileSourceRegistration->register(MapTileSourceRegistration::DEFAULT_TILE_URL);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsListAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $partnerships = $this->partnershipRepository->findByPid((int)($contentElementData['pid'] ?? 0));

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $this->partnershipRoles->collect($partnerships),
            'partnershipsWithoutRole' => $this->partnershipRoles->withoutRole($partnerships),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsTeaserAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $partnerships = $this->partnershipRepository->findByPid((int)($contentElementData['pid'] ?? 0));

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $this->partnershipRoles->collect($partnerships),
            'partnershipsWithoutRole' => $this->partnershipRoles->withoutRole($partnerships),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Method injection keeps the constructor, which project subclasses call, unchanged.
     * Final, and named after what it is for, so a subclass cannot collide with it.
     */
    final public function injectFilterTypeResolver(FilterTypeResolver $filterTypeResolver): void
    {
        $this->filterTypeResolver = $filterTypeResolver;
    }

    /**
     * Method injection for the same reason as {@see injectFilterTypeResolver()}.
     */
    final public function injectPartnershipRoles(PartnershipRoles $partnershipRoles): void
    {
        $this->partnershipRoles = $partnershipRoles;
    }

    /**
     * Method injection for the same reason as {@see injectFilterTypeResolver()}.
     */
    final public function injectMapTileSourceRegistration(MapTileSourceRegistration $mapTileSourceRegistration): void
    {
        $this->mapTileSourceRegistration = $mapTileSourceRegistration;
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
