<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The partnerships of a page, rendered in each language by the partnerships list and
 * teaser plugins and by the `PartnershipProcessor` of the page (ACE-430).
 *
 * Page 2 carries three partnerships with the role "Research partner", translated into
 * German as "Forschungspartner". Partnership 1 is translated as 11 onto the page
 * translation 102, the way translating the page does it. Partnership 2 is translated as
 * 12, a copy attached to the default language page 2, the way localizing a role did it
 * until ACE-874. Partnership 3 is not translated. Every partner page has a German
 * translation. Both callers pass the uid of the default language page, also when the
 * page renders in German.
 */
final class PartnershipsPerLanguageTest extends AbstractAcademicPartnersTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipsPerLanguage/partnershipsListPage.csv');
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
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/PartnershipProcessor.typoscript',
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

    /**
     * `strict` is what the development seed configures, `fallback` the other mode that
     * overlays records. Which partnerships render does not depend on it.
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
     * The partners of the items of a partnership plugin, in the order they render.
     *
     * @return list<string>
     */
    private function listedPartners(string $content, string $pluginClass): array
    {
        return $this->textsOf(
            $content,
            sprintf('//div[contains(concat(" ", normalize-space(@class), " "), " %s ")]//article[contains(concat(" ", normalize-space(@class), " "), " ace-item ")]//a', $pluginClass),
        );
    }

    /**
     * The headings of the role groups of a partnership plugin.
     *
     * @return list<string>
     */
    private function listedRoles(string $content, string $pluginClass): array
    {
        return $this->textsOf(
            $content,
            sprintf('//div[contains(concat(" ", normalize-space(@class), " "), " %s ")]//div[contains(concat(" ", normalize-space(@class), " "), " ace-itemlist ")]/div[@class="row"]/*[not(self::div)]', $pluginClass),
        );
    }

    /**
     * The partnerships the `PartnershipProcessor` hands to the page template, as
     * "role: partner".
     *
     * @return list<string>
     */
    private function processedPartnerships(string $content): array
    {
        return $this->textsOf($content, '//span[@class="processor-partnership"]');
    }

    /**
     * The roles the `PartnershipProcessor` hands to the page template.
     *
     * @return list<string>
     */
    private function processedRoles(string $content): array
    {
        return $this->textsOf($content, '//span[@class="processor-role"]');
    }

    /**
     * @return list<string>
     */
    private function textsOf(string $content, string $xpath): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $texts = [];
        foreach ((new \DOMXPath($document))->query($xpath) ?: [] as $node) {
            $texts[] = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');
        }
        return $texts;
    }

    #[Test]
    public function theDefaultLanguageRendersEachPartnershipOnce(): void
    {
        $this->setUpSite('fallback');

        $english = $this->renderFrontendPage('https://www.acme.com/home');

        foreach (['academic-partnerships-list', 'academic-partnerships-teaser'] as $pluginClass) {
            $this->assertSame(['Alpha University', 'Beta Institute', 'Gamma Academy'], $this->listedPartners($english, $pluginClass), $pluginClass);
            $this->assertSame(['Research partner'], $this->listedRoles($english, $pluginClass), $pluginClass);
        }
        $this->assertSame(
            ['Research partner: Alpha University', 'Research partner: Beta Institute', 'Research partner: Gamma Academy'],
            $this->processedPartnerships($english),
        );
        $this->assertSame(['Research partner'], $this->processedRoles($english));
        $this->assertStringContainsString('data-count="3"', $english);
    }

    /**
     * In German the translated partnerships 1 and 2 carry the German role and partner,
     * and the untranslated partnership 3 renders as its default language record, with the
     * role and the partner the default language names. The list and the teaser keep all
     * three under one heading in German, although partnership 3 carries the role as
     * another object.
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function germanRendersEachPartnershipOnceInItsRoleGroup(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/home');

        foreach (['academic-partnerships-list', 'academic-partnerships-teaser'] as $pluginClass) {
            $this->assertSame(['Alpha Universitaet', 'Beta Institut', 'Gamma Academy'], $this->listedPartners($german, $pluginClass), $pluginClass);
            $this->assertSame(['Forschungspartner'], $this->listedRoles($german, $pluginClass), $pluginClass);
        }
        $this->assertSame(
            ['Forschungspartner: Alpha Universitaet', 'Forschungspartner: Beta Institut', 'Research partner: Gamma Academy'],
            $this->processedPartnerships($german),
        );
        $this->assertSame(['Forschungspartner'], $this->processedRoles($german));
        $this->assertStringContainsString('data-count="3"', $german);
    }
}
