<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicpartners_map` plugin in the frontend.
 *
 * The first rendering test of a partners plugin on this branch, and it exists for one
 * question: which partners reach the map, and what the map is when none of them does.
 *
 * `Partner` types both coordinates as non-nullable `float` defaulting to `0`, so a
 * partner that was never geocoded used to arrive in the template as the perfectly valid
 * pair `0/0` and was drawn off the coast of Africa. ACE-708 taught the frontend module
 * to refuse such a record; this asserts the other half - that it never reaches the page
 * (ACE-709).
 */
final class AcademicPartnersMapPluginTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersMapPlugin/' . $dataSet . '.csv');
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
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    #[Test]
    public function mapPluginRendersLocatedPartnersAsMarkers(): void
    {
        $this->setUpTestCase('partnerMapPage');

        $content = $this->renderHomePage();

        $this->assertStringContainsString('academic-partners-map', $content);
        $this->assertStringContainsString('id="map-partners"', $content);
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('data-lat="48.137154"', $content);
        $this->assertStringContainsString('data-lng="11.576124"', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Gamma Hidden Partner', $content);
    }

    /**
     * "Delta Regional College" carries `NULL` in both columns. The model cannot express
     * that - Extbase leaves the property at its default - so `data-lat="0"` is what has
     * to be absent, never `data-lat=""`, which nothing can produce.
     */
    #[Test]
    public function mapPluginOmitsPartnersWithoutCoordinates(): void
    {
        $this->setUpTestCase('partnerMapPage');

        $content = $this->renderHomePage();

        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
        $this->assertStringNotContainsString('id="partner-13"', $content);
        $this->assertStringNotContainsString('data-lat="0"', $content);
    }

    /**
     * The other half of the rule, and the one a misplaced flag breaks silently: the
     * **list** is not filtered. A partner without coordinates is still a perfectly good
     * list entry, and dropping it there would remove partners from a page nobody was
     * looking at a map on.
     */
    #[Test]
    public function listPluginKeepsPartnersWithoutCoordinates(): void
    {
        $this->setUpTestCase('partnerListPage');

        $content = $this->renderHomePage();

        $this->assertStringContainsString('academic-partners-list', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Delta Regional College', $content);
    }

    /**
     * With nothing left to draw the map is not rendered at all: an empty canvas centred
     * on Germany says nothing, and an editor cannot tell it from a broken one. The
     * Leaflet assets are not emitted for a map that is not there either.
     *
     * The fixture holds all three shapes of "no coordinates" at once: `NULL`, the stored
     * pair `0/0` and the empty string.
     */
    #[Test]
    public function mapPluginRendersAMessageWhenNoPartnerHasCoordinates(): void
    {
        $this->setUpTestCase('partnerMapPage_noLocatedPartners');

        $content = $this->renderHomePage();

        $this->assertStringContainsString('academic-partners-map', $content);
        $this->assertStringNotContainsString('id="map-partners"', $content);
        $this->assertStringNotContainsString('id="map"', $content);
        $this->assertStringContainsString('No partner with a location to show on the map.', $content);
        $this->assertStringNotContainsString('leaflet.js', $content);
    }
}
