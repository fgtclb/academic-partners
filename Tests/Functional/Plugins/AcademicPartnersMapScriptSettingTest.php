<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The switch `plugin.tx_academicpartners.assets.js`, which decides whether the partner
 * map loads its script, and with it the stylesheets of the map libraries the script
 * imports.
 *
 * It exists twice under one name, as a site setting of the map set and as a TypoScript
 * constant for a site configured through `sys_template` records, so both delivery
 * mechanisms are covered here. The partner page, which hands the switch to the same
 * partial through the `partner-data` processor, is covered by `AcademicPartnerPageMapTest`.
 */
final class AcademicPartnersMapScriptSettingTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const MAP_SET = 'fgtclb/academic-partners-map';
    private const SETTING = 'plugin.tx_academicpartners.assets.js';

    /**
     * The bare specifier of the module. It reaches the page in the statement that imports
     * it, and nowhere when no module was registered.
     */
    private const MODULE = '@fgtclb/academic-partners/frontend/map.js';

    private const LIBRARY_STYLESHEETS = [
        'vendor/leaflet/1.9.4/leaflet.css',
        'vendor/leaflet.markercluster/1.5.3/MarkerCluster.css',
        'vendor/leaflet.markercluster/1.5.3/MarkerCluster.Default.css',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPlugin/partnerMapPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theMapSetDeclaresTheSettingSwitchedOn(): void
    {
        $definitions = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::MAP_SET)?->settingsDefinitions ?? [] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $this->assertArrayHasKey(self::SETTING, $definitions);
        $this->assertSame('bool', $definitions[self::SETTING]->type);
        $this->assertTrue($definitions[self::SETTING]->default);
    }

    #[Test]
    #[DataProvider('deliveryMechanisms')]
    public function theMapLoadsItsScriptWhenTheSiteConfiguresNothing(bool $siteSet): void
    {
        $siteSet ? $this->setUpSiteSetSite() : $this->setUpStaticTemplateSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString(self::MODULE, $content);
        foreach (self::LIBRARY_STYLESHEETS as $stylesheet) {
            $this->assertStringContainsString($stylesheet, $content);
        }
    }

    public static function deliveryMechanisms(): \Generator
    {
        yield 'site set' => [true];
        yield 'static template' => [false];
    }

    #[Test]
    public function theSiteSettingSwitchesTheScriptOff(): void
    {
        $this->setUpSiteSetSite([self::SETTING => false]);

        $this->assertMapWithoutScript($this->renderFrontendPage('https://www.acme.com/home'));
    }

    #[Test]
    public function theTypoScriptConstantSwitchesTheScriptOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/NoScript.typoscript']);

        $this->assertMapWithoutScript($this->renderFrontendPage('https://www.acme.com/home'));
    }

    /**
     * Neither the module nor the stylesheets of the libraries it imports, and the markup a
     * script of the site draws the map from unchanged: the map element with its settings,
     * and the partners.
     */
    private function assertMapWithoutScript(string $content): void
    {
        $this->assertStringNotContainsString(self::MODULE, $content);
        $this->assertStringNotContainsString('frontend/map.js', $content);
        foreach (self::LIBRARY_STYLESHEETS as $stylesheet) {
            $this->assertStringNotContainsString($stylesheet, $content);
        }
        $this->assertMatchesRegularExpression('/<div\s+id="map"[^>]*data-academic-partners-max-zoom="18"/', $content);
        $this->assertSame(2, substr_count($content, 'data-academic-partners-map-partner'));
    }

    /**
     * @param list<string> $additionalConstants Read after the shipped constants, which is
     *        where an integrator overrides one.
     */
    private function setUpStaticTemplateSite(array $additionalConstants = []): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstants,
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

    /**
     * @param array<string, bool> $settings
     */
    private function setUpSiteSetSite(array $settings = []): void
    {
        // The page object only. "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::MAP_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
