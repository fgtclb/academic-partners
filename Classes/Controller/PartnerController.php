<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Controller;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PartnerController extends ActionController
{
    private FilterTypeResolver $filterTypeResolver;

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
        // instead of being left out (ACE-709). The list leaves the flag off: a
        // partner without coordinates is still a perfectly good list entry.
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

        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
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

        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
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

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
