<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Factory;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryFilterNormalizer $categoryFilterNormalizer,
    ) {}

    /**
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $contentElementData
     */
    public function createDemandObject(
        ?array $demandFromForm,
        array $settings,
        array $contentElementData
    ): PartnerDemand {
        $demand = GeneralUtility::makeInstance(PartnerDemand::class);
        $categoryCollection = null;

        // Intitialise demand from settings if there is no demand from form
        if ($demandFromForm === null) {
            if (isset($settings['sorting'])) {
                [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $settings['sorting']);
                $demand->setSortingField($sortingField);
                $demand->setSortingDirection($sortingDirection);
            }

            if (isset($settings['categories'])
                && (int)$settings['categories'] > 0
            ) {
                $categoryCollection = $this->categoryRepository->getByDatabaseFields('partners', (int)$contentElementData['uid']);
            }
        } else {
            // Either use combined sorting or separate sorting field and direction
            if (isset($demandFromForm['sorting'])) {
                $demand->setSorting($demandFromForm['sorting']);
            } else {
                if (isset($demandFromForm['sortingField'])) {
                    $demand->setSortingField($demandFromForm['sortingField']);
                }
                if (isset($demandFromForm['sortingDirection'])) {
                    $demand->setSortingDirection($demandFromForm['sortingDirection']);
                }
            }

            if (isset($demandFromForm['filterCollection'])) {
                $categoryCollection = $this->categoryRepository->findByGroupAndUidList(
                    'partners',
                    $this->categoryFilterNormalizer->toUidList($demandFromForm['filterCollection']),
                );
            }
        }

        if ($categoryCollection !== null) {
            $demand->setFilterCollection(new FilterCollection($categoryCollection));
        }

        // Set demand properties, which are always defined by plugin settings
        $demand->setShowHiddenRecords((bool)($settings['showHiddenRecords'] ?? false));
        $demand->setPages([]);
        if (isset($contentElementData['pages'])
            && is_string($contentElementData['pages'])
            && $contentElementData['pages'] !== ''
        ) {
            $demand->setPages(GeneralUtility::intExplode(',', $contentElementData['pages']));
        }

        return $demand;
    }

    /**
     * The reverse of {@see createDemandObject()}: the arguments of a list URL that
     * demands the same selection, read from what the factory accepted rather than from
     * the request. A category that is not a partner category, a uid no category has and
     * the referrer and request hash fields of the form are therefore never part of it.
     *
     * The sorting is always included, even when it equals the default. Only a request
     * without any demand argument applies the content element's preset categories, so a
     * visitor who cleared a preset category would otherwise get it back. The categories
     * are one comma separated list, see {@see CategoryFilterNormalizer::toFilterArgument()},
     * and are left out when nothing is filtered.
     *
     * @return array<string, mixed>
     */
    public function createDemandArguments(PartnerDemand $demand): array
    {
        $arguments = [
            'sortingField' => $demand->getSortingField(),
            'sortingDirection' => $demand->getSortingDirection(),
        ];
        $filterArgument = $this->categoryFilterNormalizer->toFilterArgument($demand->getFilterCollection());
        if ($filterArgument !== '') {
            $arguments['filterCollection'] = ['categories' => $filterArgument];
        }

        return $arguments;
    }
}
