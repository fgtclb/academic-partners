<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBase\Persistence\HiddenRecordsFetcher;
use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\FilterTypeResolver;
use GeorgRinger\NumberedPagination\NumberedPagination;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class PartnerController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly PartnershipRepository $partnershipRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly DemandFactory $partnerDemandFactory,
        private readonly ExtensionService $filterRedirectExtensionService,
        private readonly FilterTypeResolver $filterTypeResolver,
        private readonly HiddenRecordsFetcher $hiddenRecordsFetcher,
    ) {}

    /**
     * `visitorSelection` tells the template whether the request carried a demand. Only a
     * request without one shows the selection the content element presets, so a link back
     * to that selection is useful only while it is `true`.
     *
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        $context = $this->pluginControllerActionContext();
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $this->redirectFilterSubmission($contentElementData);
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        // What the request asked for, read before the demand event: the page and the arguments
        // the pagination links carry. A listener acts on every request, the one a page link
        // leads to included, so its changes need not travel in the URL - and a listener that
        // hands back a demand of its own would otherwise pin the list to page one.
        $requestedPage = $demandObject->getCurrentPage();
        $requestedArguments = $this->partnerDemandFactory->createDemandArguments($demandObject);

        /** @var ModifyPartnerDemandEvent $demandEvent */
        $demandEvent = $this->eventDispatcher->dispatch(new ModifyPartnerDemandEvent($demandObject, $context));
        $demandObject = $demandEvent->getDemand();

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        /** @var ModifyPartnerListEvent $listEvent */
        $listEvent = $this->eventDispatcher->dispatch(new ModifyPartnerListEvent(
            partners: $partners,
            categories: $categories,
            demand: $demandObject,
            view: $this->view,
            pluginControllerActionContext: $context,
        ));

        $partners = $listEvent->getPartners();
        $categories = $listEvent->getCategories();
        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
            'visitorSelection' => $demand !== null,
        ]);
        $this->assignPagination($partners, $requestedPage, $requestedArguments);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * `visitorSelection` tells the template whether the request carried a demand. Only a
     * request without one shows the selection the content element presets, so a link back
     * to that selection is useful only while it is `true`.
     *
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function mapAction(?array $demand = null): ResponseInterface
    {
        $context = $this->pluginControllerActionContext();
        /** @var array<string, mixed> $contentElementData */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $this->redirectFilterSubmission($contentElementData);
        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        /** @var ModifyPartnerDemandEvent $demandEvent */
        $demandEvent = $this->eventDispatcher->dispatch(new ModifyPartnerDemandEvent($demandObject, $context));
        $demandObject = $demandEvent->getDemand();

        // A partner without coordinates cannot be drawn and would end up at 0/0
        // instead of being left out (ACE-562), and a partner hidden from the map
        // ("Show on map") is left out as well. This runs after the demand event on
        // purpose: a listener may widen the map's demand, but not back onto either.
        $demandObject->setDrawableOnly(true);

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', ...array_values($partners->toArray()));

        /** @var ModifyPartnerListEvent $listEvent */
        $listEvent = $this->eventDispatcher->dispatch(new ModifyPartnerListEvent(
            partners: $partners,
            categories: $categories,
            demand: $demandObject,
            view: $this->view,
            pluginControllerActionContext: $context,
        ));

        $categories = $listEvent->getCategories();
        $this->view->assignMultiple([
            'partners' => $listEvent->getPartners(),
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'demand' => $demandObject,
            'categories' => $categories,
            'filterTypes' => $this->filterTypeResolver->resolveFromSettings($categories, $this->settings),
            'visitorSelection' => $demand !== null,
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsListAction(): ResponseInterface
    {
        $context = $this->pluginControllerActionContext();
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
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * @return ResponseInterface
     */
    public function partnershipsTeaserAction(): ResponseInterface
    {
        $context = $this->pluginControllerActionContext();
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
            'record' => $this->getCurrentContentRecord($this->getCurrentContentObjectRenderer()),
            'partnerships' => $partnerships,
            'partnershipRoles' => $roles,
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    /**
     * Answers a submission of the filter and sorting form with a `303` to the same action,
     * carrying the selection as GET arguments, so a filtered list has a URL that can be
     * bookmarked, shared and reloaded.
     *
     * The demand is read from the body of the POST only. Extbase merges the arguments of
     * the URL into those of the body, and the URL of a filtered list carries a demand of
     * its own: merged, a category the visitor cleared would come back from the URL. A POST
     * whose body carries no demand of this plugin is another plugin's form and is left
     * alone.
     *
     * It runs before the demand event: the URL carries the visitor's selection, and a
     * listener acts on the request that follows the redirect, not on the submission.
     *
     * The redirect is thrown rather than returned. A returned one reaches the browser on
     * TYPO3 v13 only through `header()`: the response the middlewares see is a `200`, and
     * the whole page renders for nothing. The exception ends the request at the
     * `ResponsePropagation` middleware on v13 and v14 alike.
     *
     * @param array<string, mixed> $contentElementData
     * @throws PropagateResponseException
     */
    private function redirectFilterSubmission(array $contentElementData): void
    {
        if ($this->request->getMethod() !== 'POST') {
            return;
        }
        $pluginNamespace = $this->filterRedirectExtensionService->getPluginNamespace(
            $this->request->getControllerExtensionName(),
            $this->request->getPluginName(),
        );
        $parsedBody = $this->request->getParsedBody();
        $pluginArguments = is_array($parsedBody) ? ($parsedBody[$pluginNamespace] ?? null) : null;
        $demand = is_array($pluginArguments) ? ($pluginArguments['demand'] ?? null) : null;
        if (!is_array($demand)) {
            return;
        }

        /** @var array<string, mixed> $demand */
        $demandObject = $this->partnerDemandFactory->createDemandObject($demand, $this->settings, $contentElementData);
        throw new PropagateResponseException(
            $this->redirect(
                $this->request->getControllerActionName(),
                null,
                null,
                ['demand' => $this->partnerDemandFactory->createDemandArguments($demandObject)],
            ),
            1790226082,
        );
    }

    /**
     * Splits the list into pages when the content element enables it, the way the profile
     * list of `academic_persons` does: numbered page links when `numbered_pagination` is
     * loaded, the core pagination otherwise.
     *
     * The paginator pages the result the list event handed back, and the categories of the
     * filter stay those of the whole result. `demandArguments` is the demand the request
     * asked for, as a URL carries it; the pagination links add the page to it, so they keep
     * the filter and the sorting - including an editor's preselection, which only a request
     * without any demand argument would apply.
     *
     * @param QueryResultInterface<int, Partner> $partners
     * @param array<string, mixed> $requestedArguments
     */
    private function assignPagination(QueryResultInterface $partners, int $requestedPage, array $requestedArguments): void
    {
        if (!(bool)($this->settings['paginationEnabled'] ?? false)) {
            return;
        }
        $resultsPerPage = (int)($this->settings['pagination']['resultsPerPage'] ?? 0);
        $numberOfLinks = (int)($this->settings['pagination']['numberOfLinks'] ?? 0);

        $paginator = new QueryResultPaginator(
            $partners,
            $requestedPage,
            $resultsPerPage > 0 ? $resultsPerPage : 10,
        );
        // The paginator executes the query of the page on its own, and the template renders
        // that result, so it is fetched like the one of the repository.
        $paginatedItems = $paginator->getPaginatedItems();
        if ($paginatedItems instanceof QueryResultInterface) {
            $this->hiddenRecordsFetcher->fetch($paginatedItems);
        }
        if (ExtensionManagementUtility::isLoaded('numbered_pagination')
            && class_exists(NumberedPagination::class)
        ) {
            $pagination = new NumberedPagination($paginator, $numberOfLinks > 0 ? $numberOfLinks : 5);
        } else {
            $pagination = new SimplePagination($paginator);
        }

        $this->view->assignMultiple([
            'paginator' => $paginator,
            'pagination' => $pagination,
            'demandArguments' => $requestedArguments,
        ]);
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }

    private function pluginControllerActionContext(): PluginControllerActionContextInterface
    {
        return new PluginControllerActionContext($this->request, $this->settings);
    }
}
