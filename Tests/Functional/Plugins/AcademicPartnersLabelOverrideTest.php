<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation this extension
 * renders, on every supported core version: a label comes from `locallang.xlf`, an override
 * of the extension (`plugin.tx_academicpartners`) replaces it, and an override of the plugin
 * (`plugin.tx_academicpartners_<plugin>`) replaces both.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_partners` instead.
 *
 * TYPO3 v12 and v13 also keep the labels of a language file, overrides included,
 * for the rest of the request: once one translation has read the right path, the ones after
 * it show its overrides whatever name they pass. A case therefore proves its own call only
 * as the first translation of the file: the plugins hide the category filter, whose partial
 * translates correctly since ACE-739, and the sorting option is rendered from a partial of
 * this test that holds the select alone, so the options are read with the default name of
 * the view helper. The cases after the first one are held by the check of the extension
 * names of all translations.
 *
 * The list is on `/home`, the map on `/map`. Alpha University is a European partner with an
 * address in Germany and no coordinates, so the map has no partner to show.
 */
final class AcademicPartnersLabelOverrideTest extends AbstractAcademicPartnersTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersLabelOverride/pages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<non-empty-string> $setupFiles
     */
    private function setUpSite(array $setupFiles, string $setup): void
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
                    ...$setupFiles,
                ],
            ],
        );
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    private function renderPluginPage(string $url, string $setup, ?string $partialRootPath = null): string
    {
        if ($partialRootPath !== null) {
            $setup .= LF . 'plugin.tx_academicpartners.view.partialRootPaths.100 = ' . $partialRootPath;
        }
        $this->setUpSite(
            [
                'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
            ],
            $setup,
        );

        return $this->normalizeWhitespace($this->renderFrontendPage($url));
    }

    /**
     * The partner page, rendered by the page template of the page type - outside of any
     * plugin, so only the extension path applies.
     */
    private function renderPartnerPage(string $setup): string
    {
        $this->setUpSite(
            [
                'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript',
                'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                'EXT:academic_partners/Configuration/TypoScript/ContentLoad/setup.typoscript',
            ],
            $setup,
        );

        return $this->normalizeWhitespace($this->renderFrontendPage('https://www.acme.com/alpha-university'));
    }

    private function normalizeWhitespace(string $content): string
    {
        return (string)preg_replace('/\s+/', ' ', $content);
    }

    /**
     * @param array<string, string> $overrides TypoScript path => label
     */
    private function localLang(string $key, array $overrides): string
    {
        $setup = '';
        foreach ($overrides as $path => $label) {
            $setup .= $path . '._LOCAL_LANG.default.' . $key . ' = ' . $label . LF;
        }
        return $setup;
    }

    /**
     * Every kind of translation a plugin of this extension renders: a label of a partial, one
     * whose key is built from a variable, and the options of the sorting select, which its
     * view helper translates in PHP with a full `LLL:` reference.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5?: string}>
     */
    public static function pluginTranslationDataProvider(): \Generator
    {
        yield 'list, label of the sorting partial' => [
            'https://www.acme.com/home', 'list', 'sorting.field.label', 'Sorting field',
            '<label for="sortingField" class="form-label"> %s </label>',
        ];
        yield 'list, category type of a partner, key from a variable' => [
            'https://www.acme.com/home', 'list', 'sys_category.partners.region', 'Region',
            '<b>%s:</b> <span> Europe </span>',
        ];
        yield 'list, sorting option, translated by the view helper' => [
            'https://www.acme.com/home', 'list', 'sorting.field.title', 'Title',
            '<option value="title" selected="selected">%s</option>',
            'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/AcademicPartnersLabelOverride/SortingSelectOnly/',
        ];
        yield 'map, message of the map template' => [
            'https://www.acme.com/map', 'map', 'map.noLocatedPartnersFound', 'No partner with a location to show on the map.',
            '<p class="academic-partners-map-empty"> %s </p>',
        ];
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheLanguageFile(string $url, string $plugin, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderPluginPage($url, '', $partialRootPath));
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfTheExtension(string $url, string $plugin, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicpartners' => 'Extension label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function aPluginRendersTheLabelOfThePlugin(string $url, string $plugin, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicpartners_' . $plugin => 'Plugin label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $url, string $plugin, string $key, string $label, string $markup, ?string $partialRootPath = null): void
    {
        $content = $this->renderPluginPage($url, $this->localLang($key, [
            'plugin.tx_academicpartners' => 'Extension label',
            'plugin.tx_academicpartners_' . $plugin => 'Plugin label',
        ]), $partialRootPath);

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }

    /**
     * The page template translates a label of its own, a category type and the country of
     * the address, whose key is a full `LLL:` reference into the country list of the core.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function pageTranslationDataProvider(): \Generator
    {
        yield 'label of the page template' => ['academic_partners.address', 'Address', '<b>%s:</b>'];
        yield 'category type, key from a variable' => ['sys_category.partners.region', 'Region', '<b>%s:</b> <span> Europe </span>'];
        yield 'country, full reference into the core' => ['DE.name', 'Germany', '- 10115 Berlin | %s </span>'];
    }

    #[DataProvider('pageTranslationDataProvider')]
    #[Test]
    public function thePageTemplateRendersTheLabelOfTheLanguageFile(string $key, string $label, string $markup): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderPartnerPage(''));
    }

    #[DataProvider('pageTranslationDataProvider')]
    #[Test]
    public function thePageTemplateRendersTheLabelOfTheExtension(string $key, string $label, string $markup): void
    {
        $content = $this->renderPartnerPage($this->localLang($key, [
            'plugin.tx_academicpartners' => 'Extension label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }
}
