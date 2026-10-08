<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The plugin option "Show hidden records" of the `academicpartners_list` plugin on a translated
 * page (ACE-857).
 *
 * The fixture holds a visible and a hidden partner page, each with a German translation
 * that shares the visibility of its default page, and a list plugin translated to German
 * with the option switched on. The query lifts the hidden flag through its own settings,
 * while the language overlay follows the visibility of the context, so the hidden
 * partner used to appear with its English title on TYPO3 v13. TYPO3 v14.3.7 overlays
 * with the ignored enable fields of the query settings itself.
 */
final class AcademicPartnersShowHiddenRecordsTranslationTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersShowHiddenRecordsTranslation/partnerListPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    private function setUpSite(string $fallbackType): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'strict' ? [] : ['EN'],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    private function switchOffShowHiddenRecords(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        foreach ([1, 11] as $uid) {
            $flexForm = (string)$connection->select(['pi_flexform'], 'tt_content', ['uid' => $uid])->fetchOne();
            $connection->update(
                'tt_content',
                [
                    'pi_flexform' => str_replace(
                        '<field index="settings.showHiddenRecords"><value index="vDEF">1</value></field>',
                        '<field index="settings.showHiddenRecords"><value index="vDEF">0</value></field>',
                        $flexForm,
                    ),
                ],
                ['uid' => $uid],
            );
        }
    }

    /**
     * Pagination on, with room for every record on the first page.
     */
    private function switchOnPagination(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        foreach ([1, 11] as $uid) {
            $flexForm = (string)$connection->select(['pi_flexform'], 'tt_content', ['uid' => $uid])->fetchOne();
            $connection->update(
                'tt_content',
                [
                    'pi_flexform' => str_replace(
                        '<field index="settings.showHiddenRecords">',
                        '<field index="settings.paginationEnabled"><value index="vDEF">1</value></field>'
                        . '<field index="settings.pagination.resultsPerPage"><value index="vDEF">20</value></field>'
                        . '<field index="settings.showHiddenRecords">',
                        $flexForm,
                    ),
                ],
                ['uid' => $uid],
            );
        }
    }

    /**
     * `strict` is what the development seed configures, `fallback` the other mode that
     * overlays records.
     *
     * @return array<string, array{'strict'|'fallback'}>
     */
    public static function fallbackTypes(): array
    {
        return [
            'fallback type "strict"' => ['strict'],
            'fallback type "fallback"' => ['fallback'],
        ];
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListShowsTheTranslationOfAHiddenPartner(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Alpha Universitaet', $german);
        $this->assertStringContainsString('Gamma Verborgener Partner', $german);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $german);
        $this->assertStringNotContainsString('Alpha University', $german);
    }

    /**
     * The paginator executes the query of the page on its own, and the list renders that
     * result.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function paginatedTranslatedListShowsTheTranslationOfAHiddenPartner(string $fallbackType): void
    {
        $this->switchOnPagination();
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Gamma Verborgener Partner', $german);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $german);
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function defaultLanguageListShowsTheHiddenPartner(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $english = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Alpha University', $english);
        $this->assertStringContainsString('Gamma Hidden Partner', $english);
        $this->assertStringNotContainsString('Gamma Verborgener Partner', $english);
    }

    /**
     * A hidden partner without a translation follows the fallback type like any other
     * record: left out with `strict`, rendered in the default language with `fallback`.
     *
     * @return array<string, array{'strict'|'fallback', bool}>
     */
    public static function fallbackTypesRenderingUntranslatedRecords(): array
    {
        return [
            'fallback type "strict"' => ['strict', false],
            'fallback type "fallback"' => ['fallback', true],
        ];
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypesRenderingUntranslatedRecords')]
    #[Test]
    public function translatedListShowsAnUntranslatedHiddenPartnerAsTheFallbackTypeSays(string $fallbackType, bool $rendered): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame($rendered, str_contains($german, 'Untranslated Delta Partner'));
    }

    /**
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheHiddenPartnerOutWithoutTheOption(string $fallbackType): void
    {
        $this->switchOffShowHiddenRecords();
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Alpha Universitaet', $german);
        $this->assertStringNotContainsString('Gamma Verborgener Partner', $german);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $german);
    }

    /**
     * A default record and its translation that differ in visibility, with
     * `fallbackType: strict`: each is listed once, with its translation.
     */
    #[Test]
    public function strictTranslatedListShowsRecordsWhoseTranslationDiffersInVisibility(): void
    {
        $this->setUpSite('strict');

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame(1, substr_count($german, 'Gemischt Verborgene Uebersetzung'));
        $this->assertSame(1, substr_count($german, 'Gemischt Sichtbare Uebersetzung'));
        $this->assertStringNotContainsString('Mixed Visible Default', $german);
        $this->assertStringNotContainsString('Mixed Hidden Default', $german);
    }

    /**
     * The same with `fallbackType: fallback`.
     */
    #[Test]
    public function fallbackTranslatedListShowsRecordsWhoseTranslationDiffersInVisibility(): void
    {
        $this->setUpSite('fallback');

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertSame(1, substr_count($german, 'Gemischt Verborgene Uebersetzung'));
        $this->assertSame(1, substr_count($german, 'Gemischt Sichtbare Uebersetzung'));
        $this->assertStringNotContainsString('Mixed Visible Default', $german);
        $this->assertStringNotContainsString('Mixed Hidden Default', $german);
    }

    /**
     * The start time of a default record keeps deciding for its translation, which has
     * none of its own, as it does without the option.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheTranslationOfAScheduledRecordOut(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringNotContainsString('Geplante Uebersetzung', $german);
        $this->assertStringNotContainsString('Scheduled Default Record', $german);
    }

    /**
     * The end time of a default record keeps deciding for its translation, which has
     * none of its own, as it does without the option.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function translatedListLeavesTheTranslationOfAnExpiredRecordOut(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringNotContainsString('Abgelaufene Uebersetzung', $german);
        $this->assertStringNotContainsString('Expired Default Record', $german);
    }
}
