<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pins the static template this extension registers.
 *
 * "Configuration/TCA/Overrides/sys_template.php" passed the extension key
 * "academic_programs" for years, so the entry offered as "Academic Partners Page
 * Setup" pointed at the TypoScript of a different extension and the TypoScript of
 * this one was registered nowhere. What that costs an installation without site
 * sets is not cosmetic: the page template directory never enters
 * "page.10.templateRootPaths" and the partnership data processor is never
 * registered.
 *
 * The second assertion is the one that would have caught it - the first one passed
 * the whole time, because the wrong key produced a perfectly valid item of the
 * other extension.
 */
final class StaticTemplateRegistrationTest extends AbstractAcademicPartnersTestCase
{
    private const EXPECTED_VALUE = 'EXT:academic_partners/Configuration/TypoScript/';

    /**
     * @return array<string, string> label => value
     */
    private function getStaticFileItems(): array
    {
        $items = [];
        foreach ($GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'] ?? [] as $item) {
            $items[(string)($item['label'] ?? '')] = (string)($item['value'] ?? '');
        }

        return $items;
    }

    #[Test]
    public function staticTemplateIsRegistered(): void
    {
        $this->assertContains(self::EXPECTED_VALUE, $this->getStaticFileItems());
    }

    #[Test]
    public function staticTemplateOfThisExtensionIsRegisteredUnderItsOwnKey(): void
    {
        foreach ($this->getStaticFileItems() as $label => $value) {
            if (!str_contains($label, 'Academic Partners')) {
                continue;
            }
            $this->assertSame(
                self::EXPECTED_VALUE,
                $value,
                sprintf('The static template "%s" points at another extension.', $label),
            );
        }
    }
}
