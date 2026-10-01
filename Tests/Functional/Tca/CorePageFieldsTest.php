<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * The fields of TYPO3 on `pages` keep the definition of TYPO3 on every page type when this
 * extension is installed.
 *
 * `ExtensionManagementUtility::addTCAcolumns()` replaces a column of the same name as a
 * whole, so a column this extension adds under a name TYPO3 uses would change that field
 * for every page of the installation. The definition of TYPO3 is read from the TCA file of
 * the core extension, before any override.
 */
final class CorePageFieldsTest extends AbstractAcademicPartnersTestCase
{
    /**
     * The target of the page type "Link", which TYPO3 v14 added (ACE-791).
     */
    #[Test]
    #[Group('not-core-13')]
    public function theLinkKeepsTheDefinitionOfTypo3(): void
    {
        $core = $this->coreColumn('link');
        $this->assertIsArray($core);
        $column = $GLOBALS['TCA']['pages']['columns']['link'];

        $this->assertSame($core['label'], $column['label']);
        $this->assertArrayNotHasKey('exclude', $column);
        $this->assertTrue($column['config']['required']);
        $this->assertSame(['params', 'target'], $column['config']['appearance']['allowedOptions']);
    }

    /**
     * TYPO3 v13 has no column of that name, so this extension adds its own.
     */
    #[Test]
    #[Group('not-core-14')]
    public function theLinkOfThisExtensionIsAddedOnTypo3V13(): void
    {
        $this->assertNull($this->coreColumn('link'));
        $this->assertSame(
            'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:columns.link.label',
            $GLOBALS['TCA']['pages']['columns']['link']['label'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function coreColumn(string $name): ?array
    {
        $tca = require ExtensionManagementUtility::extPath('core') . 'Configuration/TCA/pages.php';

        return $tca['columns'][$name] ?? null;
    }
}
