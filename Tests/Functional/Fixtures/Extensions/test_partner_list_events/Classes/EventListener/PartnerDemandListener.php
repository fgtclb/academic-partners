<?php

declare(strict_types=1);

namespace TESTS\TestPartnerListEvents\EventListener;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Changes the partner demand, driven by plugin settings so that one fixture extension
 * serves every scenario: a test includes the TypoScript file of the behaviour it wants and
 * the listener stays inert for every other test of the same class.
 */
final class PartnerDemandListener
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[AsEventListener(identifier: 'test-partner-list-events/demand')]
    public function __invoke(ModifyPartnerDemandEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        $settings = $context->getSettings();

        // A listener that acts for one plugin only. The plugin name is the one the plugin
        // was registered with ("List", "Map"), not the content element type.
        $onlyForPlugin = (string)($settings['testPartnerDemandOnlyForPlugin'] ?? '');
        if ($onlyForPlugin !== '' && $onlyForPlugin !== $context->getPluginName()) {
            return;
        }

        // Replaces the demand with a fresh one, which is what a listener that builds its
        // own demand from scratch does. On the map, a fresh demand has `drawableOnly` off
        // again - the controller sets it after this event, so it cannot widen the map back
        // onto partners at 0/0.
        //
        // The optional page restriction is what makes the *replacement* observable: a
        // listener that only mutates the demand it was handed would be adopted even by a
        // controller that threw the event's demand away.
        if ((string)($settings['testPartnerDemandReplaceWithFresh'] ?? '') === '1') {
            $replacement = new PartnerDemand();
            $pages = GeneralUtility::intExplode(',', (string)($settings['testPartnerDemandReplacementPages'] ?? ''), true);
            if ($pages !== []) {
                $replacement->setPages($pages);
            }
            $event->setDemand($replacement);
            return;
        }

        $category = (int)($settings['testPartnerDemandCategory'] ?? 0);
        if ($category > 0) {
            $event->getDemand()->setFilterCollection(new FilterCollection(
                $this->categoryRepository->findByGroupAndUidList('partners', [$category]),
            ));
        }
    }
}
