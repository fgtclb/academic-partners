<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The partner list with pagination, rendered through the templates the extension ships.
 *
 * Six partners, in title order: Alpha University, Bravo Institute, Charlie College, Delta
 * Academy and Echo School are in Europe (6), Foxtrot University is in the Americas (2).
 *
 * - `/home`: pagination on, two partners per page.
 * - `/europe`: the same, with Europe preselected by the editor.
 * - `/switched-off`: pagination stored as off.
 * - `/stored-before`: a list stored before the pagination sheet existed.
 * - `/one-per-page`: pagination on, one partner per page - six pages.
 * - `/results-per-page-empty`, `/results-per-page-zero`: pagination on, with a results
 *   per page that is no number of partners.
 * - `/map`: a map whose stored FlexForm carries pagination values, as one switched
 *   from a list to a map would - the map's own data structure has no such field.
 *
 * `numberOfLinks` is 2, so numbered pagination leaves page three out of its page links
 * where the core pagination lists every page.
 */
abstract class AbstractAcademicPartnersPaginationTestCase extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LIST_NAMESPACE = 'tx_academicpartners_list';
    protected const FORM_CLASS = 'academic-partners-filtersorting';
    protected const PARTNERS = [
        'Alpha University',
        'Bravo Institute',
        'Charlie College',
        'Delta Academy',
        'Echo School',
        'Foxtrot University',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPagination/partnerListPages.csv');
        $this->setUpPaginationSite();
    }

    /**
     * A site configured through a TypoScript record, with the constants of the extension.
     */
    protected function setUpPaginationSite(): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PaginationTwoLinks.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$this->additionalSetupFiles(),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @return list<string> TypoScript setup a test class loads after the extension's.
     */
    protected function additionalSetupFiles(): array
    {
        return [];
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return list<string> The partners the page renders, in the order it renders them.
     */
    protected function renderedPartners(string $content): array
    {
        $positions = [];
        foreach (self::PARTNERS as $partner) {
            $position = strpos($content, '>' . $partner . '<');
            if ($position !== false) {
                $positions[$partner] = $position;
            }
        }
        asort($positions);

        return array_keys($positions);
    }

    /**
     * The entries of the pagination navigation, `null` when the page renders none. An
     * entry without a link is the current page or an ellipsis.
     *
     * @return list<array{label: string, href: string|null}>|null
     */
    protected function paginationEntries(string $content): ?array
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $navigations = (new \DOMXPath($document))->query(
            '//nav[contains(concat(" ", normalize-space(@class), " "), " academic-partners-list__pagination ")]'
        );
        if ($navigations === false || $navigations->length === 0) {
            return null;
        }
        $this->assertSame(1, $navigations->length, 'The page renders more than one pagination.');

        $entries = [];
        foreach ((new \DOMXPath($document))->query('.//li', $navigations->item(0)) ?: [] as $item) {
            $link = $item instanceof \DOMElement ? $item->getElementsByTagName('a')->item(0) : null;
            $entries[] = [
                'label' => trim((string)$item->textContent),
                'href' => $link?->getAttribute('href'),
            ];
        }

        return $entries;
    }

    /**
     * @return list<string> The labels of the navigation, in order.
     */
    protected function paginationLabels(string $content): array
    {
        return array_column($this->paginationEntries($content) ?? [], 'label');
    }

    /**
     * The URL of the entry of the navigation labelled `$label` - a page number, or
     * `next`, `last` and so on.
     */
    protected function paginationLink(string $content, string $label): string
    {
        foreach ($this->paginationEntries($content) ?? [] as $entry) {
            if ($entry['label'] === $label && $entry['href'] !== null) {
                return 'https://www.acme.com' . $entry['href'];
            }
        }
        $this->fail(sprintf('The pagination has no link "%s": %s', $label, implode(' | ', $this->paginationLabels($content))));
    }

    /**
     * @return array<string, mixed>
     */
    protected function demandArguments(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $demand = $query[self::LIST_NAMESPACE]['demand'] ?? null;
        $this->assertIsArray($demand, 'The URL carries no demand: ' . $url);

        return $demand;
    }
}
