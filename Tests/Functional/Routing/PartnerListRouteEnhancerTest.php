<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Routing;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ActiveFiltersAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * The partner list and map behind the route enhancers of `Configuration/Routes/List.yaml`.
 *
 * The site configuration imports the shipped file the way a site does, so the test reads
 * the file itself and runs it through the `imports` handling of the site configuration.
 * Every combination of filter, sorting and page is generated from plugin arguments and
 * resolved back by rendering the path, because an enhancer can be broken in one direction
 * only.
 *
 * Categories: Europe (1), Americas (2) and Asia (5) are regions, University (3) is a partner
 * type, and Europe and University are translated to German. Alpha University and Gamma
 * College are European universities, Beta Institute is in the Americas.
 *
 * - `/home` (`/de/home`): the list, sorted by title ascending.
 * - `/map` (`/de/karte`): the map.
 * - `/europe`: a list with Europe preselected by the editor.
 * - `/paginated` (`/de/seitenweise`): a list with one partner per page.
 */
final class PartnerListRouteEnhancerTest extends AbstractAcademicPartnersTestCase
{
    use ActiveFiltersAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PREFIX = 'academic-partners';
    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const MAP_NAMESPACE = 'tx_academicpartners_map';
    private const FORM_CLASS = 'academic-partners-filtersorting';
    private const PARTNERS = ['Alpha University', 'Beta Institute', 'Gamma College'];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'FR' => ['id' => 2, 'title' => 'Français', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            // What every new installation gets: a URL with arguments the cache hash covers
            // and no cHash is a 404, not an uncached page.
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        // The testing framework replaces the page cache by a NullBackend, and
                        // the page cache entries are what one test counts.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnerListRouteEnhancer/partnerListAndMapPages.csv');
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
        // The tags show which categories a path resolved to.
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . "\nplugin.tx_academicpartners.filter.showActiveFilters = 1\n"],
            ['uid' => $template['uid']],
        );
        $this->writeSite();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: int, 1: int, 2: array<string, mixed>, 3: string, 4: list<string>, 5: list<string>}>
     */
    public static function listCombinations(): \Generator
    {
        yield 'filter, sorting and page' => [
            0, 7, ['filterCollection' => ['categories' => '1,3'], 'sortingField' => 'title', 'sortingDirection' => 'desc', 'currentPage' => 2],
            '/paginated/filter/europe-1,university-3/title/desc/page-2',
            ['Europe', 'University'],
            ['Alpha University'],
        ];
        yield 'filter and sorting' => [
            0, 2, ['filterCollection' => ['categories' => '1'], 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/filter/europe-1/title/desc',
            ['Europe'],
            ['Gamma College', 'Alpha University'],
        ];
        yield 'filter and page' => [
            0, 7, ['filterCollection' => ['categories' => '1'], 'currentPage' => 2],
            '/paginated/filter/europe-1/page-2',
            ['Europe'],
            ['Gamma College'],
        ];
        yield 'sorting and page' => [
            0, 7, ['sortingField' => 'title', 'sortingDirection' => 'desc', 'currentPage' => 2],
            '/paginated/title/desc/page-2',
            [],
            ['Beta Institute'],
        ];
        yield 'only a filter' => [
            0, 2, ['filterCollection' => ['categories' => '2']],
            '/home/filter/americas-2',
            ['Americas'],
            ['Beta Institute'],
        ];
        yield 'only a sorting' => [
            0, 2, ['sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/title/desc',
            [],
            ['Gamma College', 'Beta Institute', 'Alpha University'],
        ];
        yield 'only a page' => [
            0, 7, ['currentPage' => 3],
            '/paginated/page-3',
            [],
            ['Gamma College'],
        ];
        yield 'German: filter, sorting and page' => [
            1, 7, ['filterCollection' => ['categories' => '1,3'], 'sortingField' => 'title', 'sortingDirection' => 'desc', 'currentPage' => 2],
            '/de/seitenweise/filter/europa-1,universitaet-3/titel/absteigend/seite-2',
            ['Europa', 'Universität'],
            ['Alpha University'],
        ];
        yield 'German: only a sorting' => [
            1, 2, ['sortingField' => 'lastUpdated', 'sortingDirection' => 'asc'],
            '/de/home/zuletzt-aktualisiert/aufsteigend',
            [],
            ['Alpha University', 'Beta Institute', 'Gamma College'],
        ];
        yield 'German: only a page' => [
            1, 7, ['currentPage' => 2],
            '/de/seitenweise/seite-2',
            [],
            ['Beta Institute'],
        ];
    }

    /**
     * @param array<string, mixed> $demand
     * @param list<string> $tags
     * @param list<string> $partners
     */
    #[DataProvider('listCombinations')]
    #[Test]
    public function everyListCombinationGeneratesAPathThatResolves(int $languageId, int $pageId, array $demand, string $path, array $tags, array $partners): void
    {
        $uri = $this->generateUri($languageId, $pageId, self::LIST_NAMESPACE, 'list', $demand);

        $this->assertSame('https://www.acme.com' . $path, $uri);

        $content = $this->renderFrontendPage($uri);
        $this->assertSame($tags, array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertPartners($partners, $content);
    }

    /**
     * @return \Generator<string, array{0: int, 1: array<string, mixed>, 2: string, 3: list<string>, 4: array{0: string, 1: string}}>
     */
    public static function mapCombinations(): \Generator
    {
        yield 'filter and sorting' => [
            0, ['filterCollection' => ['categories' => '1'], 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/map/filter/europe-1/title/desc',
            ['Europe'],
            ['title', 'desc'],
        ];
        yield 'only a filter' => [
            0, ['filterCollection' => ['categories' => '2']],
            '/map/filter/americas-2',
            ['Americas'],
            ['title', 'asc'],
        ];
        yield 'only a sorting' => [
            0, ['sortingField' => 'lastUpdated', 'sortingDirection' => 'desc'],
            '/map/last-updated/desc',
            [],
            ['lastUpdated', 'desc'],
        ];
        yield 'German: filter and sorting' => [
            1, ['filterCollection' => ['categories' => '1'], 'sortingField' => 'sorting', 'sortingDirection' => 'asc'],
            '/de/karte/filter/europa-1/sortierung/aufsteigend',
            ['Europa'],
            ['sorting', 'asc'],
        ];
    }

    /**
     * @param array<string, mixed> $demand
     * @param list<string> $tags
     * @param array{0: string, 1: string} $sorting
     */
    #[DataProvider('mapCombinations')]
    #[Test]
    public function everyMapCombinationGeneratesAPathThatResolves(int $languageId, array $demand, string $path, array $tags, array $sorting): void
    {
        $uri = $this->generateUri($languageId, 4, self::MAP_NAMESPACE, 'map', $demand);

        $this->assertSame('https://www.acme.com' . $path, $uri);

        $content = $this->renderFrontendPage($uri);
        $this->assertSame($tags, array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertSelectedSorting($sorting, $content);
    }

    /**
     * The links the list renders itself take the routes: an active filter tag, a page link
     * of the pagination, and the redirect that answers the filter form.
     */
    #[Test]
    public function theLinksOfTheListArePaths(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/paginated/filter/europe-1,university-3/title/desc');

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame('/paginated/filter/university-3/title/desc', $tags['Europe']['href']);
        $this->assertSame('/paginated/filter/europe-1/title/desc', $tags['University']['href']);
        $this->assertStringContainsString('href="/paginated/filter/europe-1,university-3/title/desc/page-2"', $content);

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2'], 'sortingField' => 'lastUpdated', 'sortingDirection' => 'desc']],
        ]);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/home/filter/americas-2/last-updated/desc', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function theTagsOfTheMapLeadBackToTheMap(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/map/filter/americas-2,europe-1/title/asc');

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame('/map/filter/americas-2/title/asc', $tags['Europe']['href']);
        $this->assertSame('/map/filter/europe-1/title/asc', $tags['Americas']['href']);
    }

    /**
     * The sorting values follow the language of the site, and a value of another language
     * is not a second address of the same list.
     */
    #[Test]
    public function aSortingValueOfAnotherLanguageIsNotFound(): void
    {
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/de/home/title/desc')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/home/titel/absteigend')->getStatusCode());
    }

    /**
     * The enhancer declares no defaults, so the bare page stays the list as the editor
     * configured it, with the preselected category.
     */
    #[Test]
    public function theBarePageKeepsThePreselection(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/europe');

        $this->assertSame(['Europe'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertPartners(['Alpha University', 'Gamma College'], $content);
    }

    /**
     * The page is a dynamic argument, so all pages of a list share one page cache entry, as
     * all filters do. The sorting is static, so each sorting is an entry of its own.
     */
    #[Test]
    public function pagesShareOneCachedPageAndEachSortingHasItsOwn(): void
    {
        $this->getConnectionPool()->getConnectionForTable('cache_pages')->truncate('cache_pages');
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');

        $this->assertPartners(['Alpha University'], $this->renderFrontendPage('https://www.acme.com/paginated/title/asc/page-1'));
        $this->assertSame(1, $this->countCachedPages());
        $this->assertPartners(['Beta Institute'], $this->renderFrontendPage('https://www.acme.com/paginated/title/asc/page-2'));
        $this->assertSame(1, $this->countCachedPages());

        $this->assertPartners(['Gamma College'], $this->renderFrontendPage('https://www.acme.com/paginated/title/desc/page-1'));
        $this->assertSame(2, $this->countCachedPages());
    }

    /**
     * The example of the manual: a site adds the values of a further language in its own
     * configuration. The import appends the site's `localeMap` items to the shipped ones,
     * and the requirements pin each variable to a segment, not to a list of values.
     */
    #[Test]
    public function aSiteAddsTheValuesOfAFurtherLanguage(): void
    {
        $this->writeSite(true, [
            'aspects' => [
                'page_key' => ['localeMap' => [['locale' => 'fr.*', 'value' => 'page']]],
                'sorting_field' => ['localeMap' => [['locale' => 'fr.*', 'map' => ['titre' => 'title', 'mise-a-jour' => 'lastUpdated', 'tri' => 'sorting']]]],
                'sorting_direction' => ['localeMap' => [['locale' => 'fr.*', 'map' => ['croissant' => 'asc', 'decroissant' => 'desc']]]],
            ],
        ]);

        $uri = $this->generateUri(2, 2, self::LIST_NAMESPACE, 'list', ['sortingField' => 'title', 'sortingDirection' => 'desc']);

        $this->assertSame('https://www.acme.com/fr/accueil/titre/decroissant', $uri);
        $this->assertPartners(['Gamma College', 'Beta Institute', 'Alpha University'], $this->renderFrontendPage($uri));
        $this->assertSame('https://www.acme.com/de/home/titel/absteigend', $this->generateUri(1, 2, self::LIST_NAMESPACE, 'list', ['sortingField' => 'title', 'sortingDirection' => 'desc']));
    }

    #[Test]
    public function withoutTheImportAListLinkKeepsItsQueryArguments(): void
    {
        $this->writeSite(false);

        $uri = $this->generateUri(0, 2, self::LIST_NAMESPACE, 'list', ['filterCollection' => ['categories' => '2'], 'sortingField' => 'title', 'sortingDirection' => 'asc']);

        $this->assertStringStartsWith('https://www.acme.com/home?', $uri);
        $this->assertStringContainsString(rawurlencode(self::LIST_NAMESPACE . '[demand][filterCollection][categories]') . '=2', $uri);
    }

    /**
     * @param array<string, mixed> $listEnhancer what the site adds to the list enhancer
     */
    private function writeSite(bool $withImport = true, array $listEnhancer = []): void
    {
        $routing = [];
        if ($withImport) {
            $routing = [
                'imports' => [
                    ['resource' => 'EXT:academic_partners/Configuration/Routes/List.yaml'],
                ],
                'routeEnhancers' => [
                    'AcademicPartnersList' => array_merge(['limitToPages' => [2, 5, 7]], $listEnhancer),
                    'AcademicPartnersMap' => ['limitToPages' => [4]],
                ],
            ];
        }
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $routing,
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                // Falls back to English, so the content elements and the partners, which are
                // not translated, render on the German pages as well.
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
                $this->buildLanguageConfiguration(identifier: 'FR', base: '/fr/', fallbackIdentifiers: ['EN']),
            ],
        );
    }

    /**
     * @param array<string, mixed> $demand
     */
    private function generateUri(int $languageId, int $pageId, string $namespace, string $action, array $demand): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');

        return (string)$site->getRouter()->generateUri($pageId, [
            '_language' => $site->getLanguageById($languageId),
            $namespace => [
                'action' => $action,
                'controller' => 'Partner',
                'demand' => $demand,
            ],
        ]);
    }

    /**
     * The partners a list shows, in the order it shows them, and none of the others.
     *
     * @param list<string> $expected
     */
    private function assertPartners(array $expected, string $content): void
    {
        $positions = [];
        foreach (self::PARTNERS as $partner) {
            $position = strpos($content, $partner);
            if ($position !== false) {
                $positions[$partner] = $position;
            }
        }
        asort($positions);

        $this->assertSame($expected, array_keys($positions));
    }

    /**
     * @param array{0: string, 1: string} $sorting
     */
    private function assertSelectedSorting(array $sorting, string $content): void
    {
        $this->assertMatchesRegularExpression(sprintf('#<option value="%s" selected="selected">#', $sorting[0]), $content);
        $this->assertMatchesRegularExpression(sprintf('#<option value="%s" selected="selected">#', $sorting[1]), $content);
    }

    private function countCachedPages(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }
}
