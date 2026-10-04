<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\CategoryTypes\Registry\CategoryTypeRegistry;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CategoryTypesTest extends AbstractAcademicPartnersTestCase
{
    use FrontendIconsAssertionTrait;

    #[Test]
    public function extensionCategoryTypesYamlIsLoaded(): void
    {
        /** @var CategoryTypeRegistry $categoryTypeRegistry */
        $categoryTypeRegistry = $this->get(CategoryTypeRegistry::class);
        $groupedCategoryTypes = $categoryTypeRegistry->getGroupedCategoryTypes();
        $this->assertCount(1, array_keys($groupedCategoryTypes));
        $this->assertArrayHasKey('partners', $groupedCategoryTypes);
        $expected = include __DIR__ . '/Fixtures/DefaultExtensionCategoryTypes.php';
        $this->assertSame($expected, $categoryTypeRegistry->toArray());
    }

    /**
     * The group title of `Configuration/CategoryTypes.yaml` heads the types of the group in
     * the type select of a category, instead of the key `partners`.
     */
    #[Test]
    public function groupTitleHeadsTheTypesInTheTypeSelect(): void
    {
        $this->assertSame(
            'LLL:EXT:academic_partners/Resources/Private/Language/locallang.xlf:sys_category.partners.group',
            $GLOBALS['TCA']['sys_category']['columns']['type']['config']['itemGroups']['partners'] ?? null,
        );
    }

    /**
     * The declared group icon exists and is registered for inlining, with the same file in
     * the icon registry of the backend and in the frontend icon registry. The group shares
     * the drawing of the partner page type and the partner content elements.
     */
    #[Test]
    public function groupIconIsShippedAndRegistered(): void
    {
        $group = $this->get(CategoryTypeRegistry::class)->getGroup('partners');
        $this->assertNotNull($group);
        $this->assertSame('EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg', $group->getIcon());
        $this->assertTrue($group->isInlineIcon());
        $this->assertFileExists(GeneralUtility::getFileAbsFileName($group->getIcon()));

        $iconRegistry = $this->get(IconRegistry::class);
        $this->assertSame(
            CurrentColorSvgIconProvider::class,
            $iconRegistry->getIconConfigurationByIdentifier('category_types_group.partners')['provider'] ?? null,
        );
        $this->assertIconIsRegisteredInBothRegistries('category_types_group.partners');
    }
}
