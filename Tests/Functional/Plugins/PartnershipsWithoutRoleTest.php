<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The partnerships without a role, rendered by the partnerships list and teaser plugins
 * and handed to a page template by the `PartnershipProcessor` (ACE-917).
 *
 * Page 2 mixes partnerships with and without a role. In the order arranged on the page:
 * Alpha University as "Research partner", Epsilon School without a role, Beta Institute
 * without a role, Gamma Academy as "Teaching partner" and Delta College as "Research
 * partner". Epsilon School is arranged before Beta Institute although its uid is higher.
 * "Research partner" is translated into German as "Forschungspartner", and the
 * partnerships of Alpha University and Beta Institute are translated onto the page
 * translation 102. Page 4 carries partnerships without a role only, page 5 partnerships
 * with a role only. Page 6 carries Alpha University as "Research partner", Delta College
 * with a hidden role, Epsilon School with a deleted role, and a hidden partnership of
 * Beta Institute with the hidden role.
 */
final class PartnershipsWithoutRoleTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const PLUGIN_CLASSES = ['academic-partnerships-list', 'academic-partnerships-teaser'];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipsWithoutRole/partnershipsPages.csv');
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
     * The item lists of a partnership plugin in the order they render, each with the
     * heading that opens it, `null` for none, its partners and the heading elements the
     * names of its partners render in. Every item list is a row of its own directly in
     * the plugin, a role heading is its first element that is not a column.
     *
     * @return list<array{heading: string|null, partners: list<string>, nameElements: list<string>}>
     */
    private function itemLists(string $content, string $pluginClass): array
    {
        $xpath = $this->xpathOf($content);
        $itemLists = $xpath->query(sprintf(
            '//div[contains(concat(" ", normalize-space(@class), " "), " %s ")]/div[@class="row"]',
            $pluginClass,
        )) ?: [];

        $result = [];
        foreach ($itemLists as $itemList) {
            $headings = $this->textsOfNodes($xpath->query('./*[not(self::div)]', $itemList) ?: []);
            $names = $xpath->query(
                sprintf(
                    './/div[contains(concat(" ", normalize-space(@class), " "), " %s-item ")]//div[contains(concat(" ", normalize-space(@class), " "), " card-body ")]//a',
                    $pluginClass,
                ),
                $itemList,
            ) ?: [];
            $nameElements = [];
            foreach ($names as $name) {
                $nameElements[] = $name->parentNode?->nodeName ?? '';
            }
            $result[] = [
                'heading' => $headings[0] ?? null,
                'partners' => $this->textsOfNodes($names),
                'nameElements' => array_values(array_unique($nameElements)),
            ];
        }
        return $result;
    }

    /**
     * The partnerships without a role the `PartnershipProcessor` hands to the page
     * template, by the title of their partner.
     *
     * @return list<string>
     */
    private function processedPartnershipsWithoutRole(string $content): array
    {
        return $this->textsOfNodes($this->xpathOf($content)->query('//span[@class="processor-without-role"]') ?: []);
    }

    private function xpathOf(string $content): \DOMXPath
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new \DOMXPath($document);
    }

    /**
     * @param iterable<\DOMNode> $nodes
     * @return list<string>
     */
    private function textsOfNodes(iterable $nodes): array
    {
        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');
        }
        return $texts;
    }

    /**
     * The role groups come first, in the order their roles first occur on the page. The
     * partnerships without a role follow in an item list without a heading, in the order
     * arranged on the page rather than by uid. The names of their partners render one
     * level above the names in a role group, at the level of the role headings.
     */
    #[Test]
    public function partnershipsWithoutARoleRenderAfterTheRoleGroups(): void
    {
        $this->setUpSite('fallback');

        $english = $this->renderFrontendPage('https://www.acme.com/mixed');

        foreach (self::PLUGIN_CLASSES as $pluginClass) {
            $this->assertSame(
                [
                    ['heading' => 'Research partner', 'partners' => ['Alpha University', 'Delta College'], 'nameElements' => ['h3']],
                    ['heading' => 'Teaching partner', 'partners' => ['Gamma Academy'], 'nameElements' => ['h3']],
                    ['heading' => null, 'partners' => ['Epsilon School', 'Beta Institute'], 'nameElements' => ['h2']],
                ],
                $this->itemLists($english, $pluginClass),
                $pluginClass,
            );
        }
        $this->assertSame(['Epsilon School', 'Beta Institute'], $this->processedPartnershipsWithoutRole($english));
        $this->assertStringContainsString('data-count="5"', $english);
    }

    /**
     * In German a partnership without a role renders as its translation where it has one,
     * Beta Institut, and as its default language record where it has none, Epsilon
     * School. The role groups keep the heading in German (ACE-430).
     *
     * @param 'strict'|'fallback' $fallbackType
     */
    #[DataProvider('fallbackTypes')]
    #[Test]
    public function partnershipsWithoutARoleRenderAfterTheRoleGroupsInATranslation(string $fallbackType): void
    {
        $this->setUpSite($fallbackType);

        $german = $this->renderFrontendPage('https://www.acme.com/de/mixed');

        foreach (self::PLUGIN_CLASSES as $pluginClass) {
            $this->assertSame(
                [
                    ['heading' => 'Forschungspartner', 'partners' => ['Alpha Universitaet', 'Delta College'], 'nameElements' => ['h3']],
                    ['heading' => 'Teaching partner', 'partners' => ['Gamma Academy'], 'nameElements' => ['h3']],
                    ['heading' => null, 'partners' => ['Epsilon School', 'Beta Institut'], 'nameElements' => ['h2']],
                ],
                $this->itemLists($german, $pluginClass),
                $pluginClass,
            );
        }
        $this->assertSame(['Epsilon School', 'Beta Institut'], $this->processedPartnershipsWithoutRole($german));
        $this->assertStringContainsString('data-count="5"', $german);
    }

    /**
     * While no partnership of the page has a role, the partners render in one item list
     * without a heading, as before.
     */
    #[Test]
    public function aPageWithoutRolesRendersOneListWithoutHeading(): void
    {
        $this->setUpSite('fallback');

        $content = $this->renderFrontendPage('https://www.acme.com/without-roles');

        foreach (self::PLUGIN_CLASSES as $pluginClass) {
            $this->assertSame(
                [
                    ['heading' => null, 'partners' => ['Alpha University', 'Beta Institute'], 'nameElements' => ['h2']],
                ],
                $this->itemLists($content, $pluginClass),
                $pluginClass,
            );
        }
        $this->assertSame(['Alpha University', 'Beta Institute'], $this->processedPartnershipsWithoutRole($content));
    }

    /**
     * While every partnership of the page has a role, only the role groups render, and no
     * empty item list follows them.
     */
    #[Test]
    public function aPageWithRolesOnlyRendersNoListWithoutHeading(): void
    {
        $this->setUpSite('fallback');

        $content = $this->renderFrontendPage('https://www.acme.com/with-roles');

        foreach (self::PLUGIN_CLASSES as $pluginClass) {
            $this->assertSame(
                [
                    ['heading' => 'Research partner', 'partners' => ['Alpha University'], 'nameElements' => ['h3']],
                    ['heading' => 'Teaching partner', 'partners' => ['Gamma Academy'], 'nameElements' => ['h3']],
                ],
                $this->itemLists($content, $pluginClass),
                $pluginClass,
            );
        }
        $this->assertSame([], $this->processedPartnershipsWithoutRole($content));
    }

    /**
     * A role that is hidden or deleted does not resolve, so its partnerships render like
     * partnerships without a role, after the groups of the roles that do. A hidden
     * partnership stays hidden, whatever its role.
     */
    #[Test]
    public function partnershipsWithAnUnavailableRoleRenderAfterTheRoleGroups(): void
    {
        $this->setUpSite('fallback');

        $content = $this->renderFrontendPage('https://www.acme.com/unavailable-roles');

        foreach (self::PLUGIN_CLASSES as $pluginClass) {
            $this->assertSame(
                [
                    ['heading' => 'Research partner', 'partners' => ['Alpha University'], 'nameElements' => ['h3']],
                    ['heading' => null, 'partners' => ['Delta College', 'Epsilon School'], 'nameElements' => ['h2']],
                ],
                $this->itemLists($content, $pluginClass),
                $pluginClass,
            );
        }
        $this->assertSame(['Delta College', 'Epsilon School'], $this->processedPartnershipsWithoutRole($content));
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Hidden role', $content);
        $this->assertStringNotContainsString('Deleted role', $content);
    }
}
