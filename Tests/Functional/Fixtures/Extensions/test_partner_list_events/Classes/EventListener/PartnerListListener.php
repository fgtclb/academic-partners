<?php

declare(strict_types=1);

namespace TESTS\TestPartnerListEvents\EventListener;

use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Changes the queried partners and the view, driven by plugin settings - see
 * {@see PartnerDemandListener} for why.
 */
final class PartnerListListener
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    #[AsEventListener(identifier: 'test-partner-list-events/list')]
    public function __invoke(ModifyPartnerListEvent $event): void
    {
        $settings = $event->getPluginControllerActionContext()->getSettings();

        // An additional view variable, rendered by the template this fixture extension
        // ships. The demand is on the event as well, so a listener can report what it was
        // asked for.
        $marker = (string)($settings['testPartnerListMarker'] ?? '');
        if ($marker !== '') {
            $event->getView()->assign('testPartnerListMarker', sprintf(
                '%s|%s|%d',
                $marker,
                $event->getPluginControllerActionContext()->getActionName() ?? '',
                count($event->getPartners()->toArray()),
            ));
        }

        // Replaces the result with a subset. The setter is typed to a query result, so the
        // subset is narrowed on the query the result came from - and narrowed beside the
        // condition the repository built, not instead of it.
        $keptTitle = (string)($settings['testPartnerListKeepTitle'] ?? '');
        if ($keptTitle !== '') {
            $query = $event->getPartners()->getQuery();
            $constraint = $query->getConstraint();
            $subset = $query->equals('title', $keptTitle);
            $query->matching($constraint === null ? $subset : $query->logicalAnd($constraint, $subset));
            $event->setPartners($query->execute());
        }

        // Replaces the applicable categories, which is what a listener that narrowed the
        // result has to do for the filter of the plugin to match it - the controller does
        // not recompute them.
        $category = (int)($settings['testPartnerListCategory'] ?? 0);
        if ($category > 0) {
            $event->setCategories($this->categoryRepository->findByGroupAndUidList('partners', [$category]));
        }
    }
}
