<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\TsConfig;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Pins that the backend layout of the page type this extension registers reaches an
 * installation that does not use site sets.
 *
 * "Configuration/page.tsconfig" of an extension is auto-included for the whole
 * installation since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per site.
 * The import used to live in the set alone, so the layout "pagets__AcademicPartner" - the
 * value the extension's own page type carries - resolved nowhere unless that set was
 * enabled: the page properties showed "[ MISSING LABEL ]" for it and it could not be
 * picked for a new page at all.
 *
 * No site is written by this test on purpose. That is what makes it a test of the
 * global page TSconfig rather than of the set.
 */
final class BackendLayoutRegistrationTest extends AbstractAcademicPartnersTestCase
{
    #[Test]
    public function backendLayoutIsRegisteredWithoutASiteSet(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendLayoutRegistration/pages.csv');

        $backendLayouts = BackendUtility::getPagesTSconfig(1)['mod.']['web_layout.']['BackendLayouts.'] ?? [];

        $this->assertArrayHasKey('AcademicPartner.', $backendLayouts);
        $this->assertSame(
            'LLL:EXT:academic_partners/Resources/Private/Language/locallang_be.xlf:backend_layout.academic_partner',
            $backendLayouts['AcademicPartner.']['title'] ?? null,
        );
    }

    /**
     * The label of the content column was missing from the XLIFF file, so the column
     * header of the layout rendered unlabelled in the page module.
     */
    #[Test]
    public function backendLayoutColumnLabelExists(): void
    {
        $languageFile = dirname(__DIR__, 3) . '/Resources/Private/Language/locallang_be.xlf';
        $xml = simplexml_load_string((string)file_get_contents($languageFile));
        $this->assertNotFalse($xml);

        $identifiers = [];
        foreach ($xml->file->body->{'trans-unit'} as $transUnit) {
            $identifiers[] = (string)$transUnit['id'];
        }

        $this->assertContains('backend_layout.academic_partner.column.content', $identifiers);
    }
}
