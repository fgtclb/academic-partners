<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The configuration of the partner map: the site settings of the set
 * `fgtclb/academic-partners-map`, which reach the map module as data attributes of `#map`,
 * and the layout an editor chooses on the content element.
 *
 * What the module does with the attributes is tested in `Tests/JavaScript/map.test.ts`.
 * This class pins that the configured values arrive there, on a site that uses the set and
 * on a site that uses the static template.
 */
final class AcademicPartnersMapConfigurationTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    private const MAP_SET = 'fgtclb/academic-partners-map';

    /**
     * The values the map module used before they could be configured. The settings, the
     * constants and the fallback of the module all have to carry them.
     */
    private const DEFAULTS = [
        'centerLatitude' => '51.1657',
        'centerLongitude' => '10.4515',
        'zoom' => 6,
        'maxZoom' => 18,
        'padding' => 50,
        'tileUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Points &copy 2012 LINZ',
    ];

    private const ATTRIBUTES = [
        'centerLatitude' => 'data-academic-partners-center-lat',
        'centerLongitude' => 'data-academic-partners-center-lng',
        'zoom' => 'data-academic-partners-zoom',
        'maxZoom' => 'data-academic-partners-max-zoom',
        'padding' => 'data-academic-partners-padding',
        'tileUrl' => 'data-academic-partners-tile-url',
        'attribution' => 'data-academic-partners-attribution',
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
    public function theMapSetDeclaresEverySettingWithTheValueTheMapAlwaysUsed(): void
    {
        $defaults = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::MAP_SET)?->settingsDefinitions ?? [] as $definition) {
            $defaults[$definition->key] = $definition->default;
        }

        $expected = [];
        foreach (self::DEFAULTS as $name => $default) {
            $expected['plugin.tx_academicpartners.map.' . $name] = $default;
        }
        $this->assertSame($expected, $defaults);
    }

    /**
     * The same values from both places: a site that reads the static template after the set
     * gets the constants back, so they have to agree with the settings.
     */
    #[Test]
    #[DataProvider('unconfiguredSites')]
    public function anUnconfiguredSiteHandsTheMapTheValuesItAlwaysUsed(bool $siteSet): void
    {
        $siteSet ? $this->setUpSiteSetSite([]) : $this->setUpStaticTemplateSite();

        $map = $this->renderedMap();

        foreach (self::DEFAULTS as $name => $default) {
            $this->assertSame((string)$default, $map->getAttribute(self::ATTRIBUTES[$name]), $name);
        }
    }

    public static function unconfiguredSites(): \Generator
    {
        yield 'site set' => [true];
        yield 'static template' => [false];
    }

    #[Test]
    public function theSiteSettingsReachTheMap(): void
    {
        $configured = [
            'centerLatitude' => '47.5162',
            'centerLongitude' => '14.5501',
            'zoom' => 7,
            'maxZoom' => 12,
            'padding' => 20,
            'tileUrl' => 'https://tiles.example.org/{z}/{x}/{y}.png',
            'attribution' => '<a href="https://tiles.example.org">Example tiles</a>',
        ];
        $settings = [];
        foreach ($configured as $name => $value) {
            $settings['plugin.tx_academicpartners.map.' . $name] = $value;
        }
        $this->setUpSiteSetSite($settings);

        $map = $this->renderedMap();

        foreach ($configured as $name => $value) {
            $this->assertSame((string)$value, $map->getAttribute(self::ATTRIBUTES[$name]), $name);
        }
    }

    #[Test]
    public function aMapElementWithoutALayoutRendersAtContentWidth(): void
    {
        $this->setUpStaticTemplateSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('<div class="academic-partners-map">', $content);
        $this->assertStringNotContainsString('academic-partners-map--full-width', $content);
    }

    #[Test]
    #[DataProvider('layouts')]
    public function theLayoutOfTheElementDecidesTheModifierClass(string $layout, bool $fullWidth): void
    {
        $this->setUpStaticTemplateSite();
        $this->setLayout($layout);

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        $container = $this->elementMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-partners-map ')]");

        $this->assertSame(
            $fullWidth,
            in_array('academic-partners-map--full-width', explode(' ', $container->getAttribute('class')), true),
        );
        // The layout changes the class and nothing else: the map is drawn either way.
        $partners = $xpath->query('//*[@id="map-partners"]/li');
        $this->assertInstanceOf(\DOMNodeList::class, $partners);
        $this->assertSame(2, $partners->length);
        // The FlexForm adds "layout" to "settings.map", and the settings of the TypoScript
        // next to it have to survive that merge.
        $this->assertSame('18', $this->elementMatching($xpath, '//*[@id="map"]')->getAttribute('data-academic-partners-max-zoom'));
    }

    public static function layouts(): \Generator
    {
        yield 'content width' => ['default', false];
        yield 'full width' => ['fullWidth', true];
    }

    private function renderedMap(): \DOMElement
    {
        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));

        return $this->elementMatching($xpath, '//*[@id="map"]');
    }

    private function setLayout(string $layout): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')->update(
            'tt_content',
            [
                'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data>'
                    . '<sheet index="sDEF"><language index="lDEF"><field index="settings.sorting"><value index="vDEF">title asc</value></field></language></sheet>'
                    . '<sheet index="layout"><language index="lDEF"><field index="settings.map.layout"><value index="vDEF">' . $layout . '</value></field></language></sheet>'
                    . '</data></T3FlexForms>',
            ],
            ['uid' => 1],
        );
    }

    private function setUpStaticTemplateSite(): void
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
        ]);
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        // The page object only, and "clear = 0" keeps what the sets contribute.
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
