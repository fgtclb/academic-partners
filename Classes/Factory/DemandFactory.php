<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Factory;

use FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\FilterCollection;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DemandFactory
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly CategoryTypeRegistry $categoryTypeRegistry
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
        $filterCollection = GeneralUtility::makeInstance(FilterCollection::class);

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
                $categoryCollection = $this->categoryRepository->getByDatabaseFields('partners', $contentElementData['uid']);
                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
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

            if ($demandFromForm['filterCollection'] !== null) {
                // Find by selected type categories
                $categoryCollection = new CategoryCollection();

                foreach ($demandFromForm['filterCollection'] as $type => $categoriesIds) {
                    $formatType = GeneralUtility::camelCaseToLowerCaseUnderscored($type);
                    $categoriesIdList = GeneralUtility::intExplode(',', $categoriesIds);
                    $categoryFilterObject = $this->categoryRepository->findByTypeAndUidList(
                        'partners',
                        $type,
                        $categoriesIdList
                    );

                    if ($categoryFilterObject === null) {
                        continue;
                    }

                    foreach ($categoryFilterObject as $Category) {
                        $categoryCollection->attach($Category);
                    }
                }

                $filterCollection = FilterCollection::createByCategoryCollection($categoryCollection);
                $categoryCollection->setTypeIdentifiers($this->categoryTypeRegistry->getCategoryTypeIdentifierByGroup('partners'));
            }
        }

        $demand->setFilterCollection($filterCollection);

        return $demand;
    }
}
