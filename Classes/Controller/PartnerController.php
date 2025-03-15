<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Controller;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use FGTCLB\AcademicPartners\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class PartnerController extends ActionController
{
    public function __construct(
        protected PartnerRepository $partnerRepository,
        protected CategoryRepository $categoryRepository,
        protected DemandFactory $partnerDemandFactory
    ) {}

    /**
     * @param array<string, mixed>|null $demand
     * @return ResponseInterface
     */
    public function listAction(?array $demand = null): ResponseInterface
    {
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

        // With version TYPO3 v12 the access to the content object renderer has changed
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/RequestLifeCycle/RequestAttributes/CurrentContentObject.html
        if (version_compare($versionInformation->getVersion(), '12.0.0', '>=')) {
            $contentObjectRenderer = $this->request->getAttribute('currentContentObject');
        } else {
            $contentObjectRenderer = $this->configurationManager->getContentObject();
        }

        /** @var ContentObjectRenderer $contentObjectRenderer */
        $contentElementData = $contentObjectRenderer->data;

        $demandObject = $this->partnerDemandFactory->createDemandObject(
            $demand,
            $this->settings,
            $contentElementData
        );

        $partners = $this->partnerRepository->findByDemand($demandObject);
        $categories = $this->categoryRepository->findAllApplicable('partners', $partners);

        $this->view->assignMultiple([
            'partners' => $partners,
            'data' => $contentElementData,
            'demand' => $demandObject,
            'categories' => $categories,
        ]);

        return $this->htmlResponse();
    }
}
