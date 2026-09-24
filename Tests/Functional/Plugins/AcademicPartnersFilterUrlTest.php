<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

/**
 * The filter and sorting form of the partner list and map submits by POST, and the plugin
 * answers that submission with a `303` to a GET URL carrying the selection - so a filtered
 * list can be bookmarked, shared and reloaded.
 *
 * The form is submitted from the page the plugin rendered, with the fields it holds -
 * the referrer and request hash fields Extbase checks included.
 *
 * Categories: Americas (2) and Europe (6) are regions, University (3) is a partner type,
 * Physics (7) is a category of no partner category type. Alpha University is a European
 * university, Beta Institute is in the Americas, Delta Regional College in Europe. The
 * content element on `/europe` preselects Europe. `/home` has a German translation,
 * `/de/home`, and so have Americas ("Amerika") and Beta Institute ("Beta Institut").
 */
final class AcademicPartnersFilterUrlTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicpartners_list';
    private const MAP_NAMESPACE = 'tx_academicpartners_map';
    private const FORM_CLASS = 'academic-partners-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
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
                        // The testing framework replaces the page cache by a NullBackend. The
                        // database backend is restored so that a filter URL can be served from
                        // the page another filter URL cached.
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersFilterUrl/partnerListPages.csv');
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
            // Falls back to English, so the content element, the region Europe and the
            // partners without a translation render on the German page as well.
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function submittingARegionRedirectsToAUrlCarryingIt(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/home', parse_url($location, PHP_URL_PATH));
        $this->assertSame(
            [
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'asc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
        // The select shows the region the URL carries, so the next change keeps it.
        $this->assertMatchesRegularExpression('#<option value="2"[^>]* selected="selected">Americas</option>#', $content);
    }

    #[Test]
    public function submittingASortingRedirectsToAUrlCarryingIt(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertRenderedInOrder($content, 'Delta Regional College', 'Beta Institute');
        $this->assertRenderedInOrder($content, 'Beta Institute', 'Alpha University');
        $this->assertStringContainsString('<option value="desc" selected="selected">', $content);
    }

    /**
     * Categories of two types become one list, in ascending uid order although the region
     * comes first in the form - one selection, one URL.
     */
    #[Test]
    public function categoriesOfSeveralTypesBecomeOneAscendingList(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '6', 'partner_type' => '3']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '3,6'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Delta Regional College', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
    }

    /**
     * Only what the demand factory accepted reaches the URL: a category of no partner
     * category type and a uid no category has are gone, and so are the referrer and request
     * hash fields of the form.
     */
    #[Test]
    public function onlyTheNormalisedSelectionReachesTheUrl(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2,7,999']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '2'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );
        $this->assertStringNotContainsString('__referrer', urldecode($location));
        $this->assertStringNotContainsString('__trustedProperties', urldecode($location));
    }

    #[Test]
    public function submittingTheMapFormRedirectsToTheMap(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/map', self::FORM_CLASS, [
            self::MAP_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '6']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/map', parse_url($location, PHP_URL_PATH));
        $this->assertSame(
            ['categories' => '6'],
            $this->demandArguments($location, self::MAP_NAMESPACE)['filterCollection'] ?? null,
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('id="partner-13"', $content);
        $this->assertStringNotContainsString('id="partner-11"', $content);
    }

    /**
     * The editor's preselection applies to the bare page only. A visitor who sets the
     * region back to "all" gets a URL without a filter - but with the sorting, because a
     * demand without any argument is what makes the factory apply the preselection again.
     */
    #[Test]
    public function clearingAPreselectedRegionShowsAllPartners(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/europe');
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);

        $response = $this->submitFrontendForm('https://www.acme.com/europe', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['sortingDirection' => 'asc', 'sortingField' => 'title'],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringContainsString('Delta Regional College', $content);
    }

    /**
     * A form that posts to the URL it is on - an override without an action, one built
     * with `addQueryString`, a script posting to `location.href` - sends its fields to a
     * URL that carries a demand already, and Extbase merges the two. The redirect is built
     * from the body alone, so a region the visitor cleared does not come back from the URL.
     */
    #[Test]
    public function aSubmissionToAFilteredUrlReplacesItsSelection(): void
    {
        $filteredUrl = $this->filteredListUrl('2');

        $response = $this->requestFrontendPage($this->frontendPostRequest($filteredUrl, [
            self::LIST_NAMESPACE => ['demand' => [
                'sortingField' => 'title',
                'sortingDirection' => 'asc',
                'filterCollection' => ['region' => '', 'partner_type' => ''],
            ]],
        ]));

        $this->assertSame(
            ['sortingDirection' => 'asc', 'sortingField' => 'title'],
            $this->demandArguments($this->assertSeeOtherWithCacheHash($response), self::LIST_NAMESPACE),
        );
    }

    /**
     * Another plugin's form posting to a filtered URL carries no demand of this plugin in
     * its body. The list renders the filter the URL carries and does not redirect.
     */
    #[Test]
    public function anotherPluginsPostToAFilteredUrlIsNotRedirected(): void
    {
        $content = $this->renderFrontendPage(
            $this->frontendPostRequest($this->filteredListUrl('2'), ['tx_someother_plugin' => ['field' => 'value']]),
        );

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
    }

    /**
     * The actions are not cacheable, so the demand is kept out of the cache hash: two
     * selections share one hash, and with it one page cache entry. A hash per selection
     * would let anybody fill the page cache by submitting combinations.
     */
    #[Test]
    public function theDemandIsNotPartOfTheCacheHash(): void
    {
        parse_str((string)parse_url($this->filteredListUrl('2'), PHP_URL_QUERY), $americas);
        parse_str((string)parse_url($this->filteredListUrl('6'), PHP_URL_QUERY), $europe);

        $this->assertNotSame($americas, $europe);
        $this->assertSame($americas['cHash'], $europe['cHash']);
    }

    /**
     * The demand needs no cache hash, also with `enforceValidation` on: a link that
     * carries nothing but the demand - built by hand, or by a template - renders the
     * filtered list. Were the demand part of the hash, it would be a 404.
     */
    #[Test]
    public function aDemandWithoutCacheHashRendersTheFilteredList(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home?tx_academicpartners_list%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=2');

        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
    }

    /**
     * Sharing one cached page is safe only because the list renders outside of it, on
     * every request. Rendered one after the other from the real page cache, each filter
     * URL shows its own selection: the first one adds one page cache entry, the second
     * one is served from it.
     */
    #[Test]
    public function filterUrlsSharingOneCachedPageShowTheirOwnSelection(): void
    {
        $americas = $this->filteredListUrl('2');
        $europe = $this->filteredListUrl('6');

        // Taking the form from the page and posting it cached pages already - the POST
        // caches the page around the plugin under the very identifier of the filter URLs,
        // before the plugin redirects. Start from an empty page cache instead.
        $this->getConnectionPool()->getConnectionForTable('cache_pages')->truncate('cache_pages');
        $this->getConnectionPool()->getConnectionForTable('cache_pages_tags')->truncate('cache_pages_tags');

        $content = $this->renderFrontendPage($americas);
        $this->assertStringContainsString('Beta Institute', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertSame(1, $this->countCachedPages());

        $content = $this->renderFrontendPage($europe);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringNotContainsString('Beta Institute', $content);
        $this->assertSame(1, $this->countCachedPages());
    }

    private function countCachedPages(): int
    {
        return $this->getConnectionPool()->getConnectionForTable('cache_pages')->count('*', 'cache_pages', []);
    }

    /**
     * The redirect stays in the language the form was submitted in. The German page shows
     * the translated region, whose option still carries the uid of the default language -
     * that uid is what reaches the URL, and what the German list reads back.
     */
    #[Test]
    public function aSubmissionOnATranslatedPageStaysInItsLanguage(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/de/home');
        $this->assertMatchesRegularExpression('#<option value="2"[^>]*>Amerika</option>#', $content);

        $response = $this->submitFrontendForm('https://www.acme.com/de/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('/de/home', parse_url($location, PHP_URL_PATH));
        $this->assertSame(['categories' => '2'], $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null);
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Beta Institut<', $content);
        $this->assertStringNotContainsString('Alpha University', $content);
        $this->assertMatchesRegularExpression('#<option value="2"[^>]* selected="selected">Amerika</option>#', $content);
    }

    /**
     * A POST that carries no demand of this plugin is somebody else's form - another
     * plugin on the same page. The list renders as usual and leaves the request alone.
     */
    #[Test]
    public function aPostWithoutADemandIsNotRedirected(): void
    {
        $content = $this->renderFrontendPage(
            $this->frontendPostRequest('https://www.acme.com/home', ['tx_someother_plugin' => ['field' => 'value']]),
        );

        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Beta Institute', $content);
    }

    private function filteredListUrl(string $region): string
    {
        return $this->assertSeeOtherWithCacheHash($this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['region' => $region]]],
        ]));
    }

    /**
     * The demand arguments of a redirect target. They arrive in alphabetical order,
     * because the page router sorts the query arguments by key (`PageArguments`).
     *
     * @return array<string, mixed>
     */
    private function demandArguments(string $location, string $pluginNamespace): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? null;
        $this->assertIsArray($demand, 'The redirect target carries no demand: ' . $location);

        return $demand;
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $firstPosition = strpos($content, $first);
        $secondPosition = strpos($content, $second);
        $this->assertNotFalse($firstPosition, $first . ' is not rendered.');
        $this->assertNotFalse($secondPosition, $second . ' is not rendered.');
        $this->assertLessThan($secondPosition, $firstPosition, $first . ' is not rendered before ' . $second . '.');
    }
}
