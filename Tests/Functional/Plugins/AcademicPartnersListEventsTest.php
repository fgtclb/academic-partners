<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

/**
 * What an installed extension can do to the partner list and map, through
 * `ModifyPartnerDemandEvent` and `ModifyPartnerListEvent`.
 *
 * `EXT:test_partner_list_events` ships one listener per event. Both stay inert until a plugin
 * setting asks for something, so each test below includes the TypoScript file of the behaviour
 * it is about. That both plugins render unchanged while *no* extension listens is what
 * `AcademicPartnersPluginTest` asserts - it does not load this fixture.
 *
 * The list is told apart from the map in the markup by the link: the list renders the partner
 * page as an `href`, the map as a `data-link` of an `<li id="partner-<uid>">`.
 */
final class AcademicPartnersListEventsTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait {
        frontendPluginTestConfiguration as sharedFrontendPluginTestConfiguration;
    }
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-partner-list-events');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The Extbase class schema cache stays in memory for this class. TYPO3 core writes it
     * from the destructor of the reflection service, and when the garbage collector runs that
     * destructor inside another serialize(), the outer payload ends up with back-references
     * it cannot be read back with. On TYPO3 v14 this class hit it whenever a certain set of
     * test classes ran before it in the same process (the defect is recorded with ACE-725,
     * the same workaround with ACE-729 and ACE-740). An in-memory cache is never serialized.
     *
     * @param array<string, mixed> $additionalConfiguration
     * @return array<string, mixed>
     */
    protected function frontendPluginTestConfiguration(array $additionalConfiguration = []): array
    {
        return $this->sharedFrontendPluginTestConfiguration(array_replace_recursive([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'extbase' => [
                            'backend' => TransientMemoryBackend::class,
                        ],
                    ],
                ],
            ],
        ], $additionalConfiguration));
    }

    /**
     * @param string[] $listenerTypoScriptFiles The behaviour the fixture listeners are asked for.
     */
    private function setUpTestCase(string $dataSet, array $listenerTypoScriptFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnersPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ],
                    $listenerTypoScriptFiles,
                ),
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The demand a listener hands back is the one that is queried: the content element has no
     * category selection of its own, so every partner would be listed without the listener.
     */
    #[Test]
    public function aDemandListenerNarrowsTheList(): void
    {
        $this->setUpTestCase('partnerListPage_categorized', [
            'EXT:test_partner_list_events/Configuration/TypoScript/CategoryFilter.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="/beta-institute"', $content);
        $this->assertStringNotContainsString('href="/alpha-university"', $content);
        $this->assertStringNotContainsString('href="/delta-regional-college"', $content);
    }

    /**
     * The context tells the two plugins apart, so a listener can narrow the map while the list
     * on the same page keeps showing every partner.
     */
    #[Test]
    public function aDemandListenerCanActOnTheMapAlone(): void
    {
        $this->setUpTestCase('partnerListAndMapPage_categorized', [
            'EXT:test_partner_list_events/Configuration/TypoScript/CategoryFilterMapOnly.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The map draws the one partner of the demanded category.
        $this->assertStringContainsString('id="partner-11"', $content);
        $this->assertStringNotContainsString('id="partner-10"', $content);
        $this->assertStringNotContainsString('id="partner-13"', $content);
        // The list is untouched.
        $this->assertStringContainsString('href="/alpha-university"', $content);
        $this->assertStringContainsString('href="/beta-institute"', $content);
        $this->assertStringContainsString('href="/delta-regional-college"', $content);
    }

    /**
     * A partner without coordinates ends up at 0/0 instead of being left out (ACE-562), so the
     * map restricts the demand to drawable partners *after* the event. A listener that hands
     * back a fresh demand - with the restriction off again - therefore still draws none.
     */
    #[Test]
    public function theMapStillSkipsPartnersWithoutCoordinatesWhenAListenerReplacesTheDemand(): void
    {
        $this->setUpTestCase('partnerMapPage', [
            'EXT:test_partner_list_events/Configuration/TypoScript/FreshDemand.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('id="partner-10"', $content);
        $this->assertStringContainsString('id="partner-11"', $content);
        $this->assertStringNotContainsString('id="partner-13"', $content);
    }

    /**
     * `setDemand()` rather than a mutation of the demand the controller built: the demand
     * that is queried has to be the one the listener handed back, not the one the factory
     * produced. The replacement restricts the list to one page, which the content element
     * does not do.
     */
    #[Test]
    public function theDemandAListenerHandsBackIsTheOneThatIsQueried(): void
    {
        $this->setUpTestCase('partnerListPage', [
            'EXT:test_partner_list_events/Configuration/TypoScript/FreshDemandOnePage.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="/delta-regional-college"', $content);
        $this->assertStringNotContainsString('href="/alpha-university"', $content);
        $this->assertStringNotContainsString('href="/beta-institute"', $content);
    }

    /**
     * The list event fires in the map action as well, not only in the list action. The map
     * renders no view variable of its own, so the replacement of the result is what proves
     * the dispatch is there.
     */
    #[Test]
    public function aListListenerReachesTheMapAsWell(): void
    {
        $this->setUpTestCase('partnerMapPage', [
            'EXT:test_partner_list_events/Configuration/TypoScript/ReplaceResult.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('id="partner-11"', $content);
        $this->assertStringNotContainsString('id="partner-10"', $content);
    }

    /**
     * The categories are computed before the list event and are not recomputed after it, so
     * a listener that wants the filter of the plugin to differ sets them. The partners are
     * left alone here, which is what tells the two setters apart.
     */
    #[Test]
    public function aListListenerReplacesTheApplicableCategories(): void
    {
        $this->setUpTestCase('partnerListPage_categorized', [
            'EXT:test_partner_list_events/Configuration/TypoScript/ReplaceCategories.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The filter offers the one category the listener left.
        $this->assertStringContainsString('>Americas</option>', $content);
        $this->assertStringNotContainsString('>Europe</option>', $content);
        // The partners themselves are untouched.
        $this->assertStringContainsString('href="/alpha-university"', $content);
        $this->assertStringContainsString('href="/beta-institute"', $content);
        $this->assertStringContainsString('href="/delta-regional-college"', $content);
    }

    /**
     * The list event carries the view, so a listener assigns variables a project template
     * renders. The fixture extension ships such a template and puts it in front of the shipped
     * one; the value proves the action and the queried partners reach the listener as well.
     */
    #[Test]
    public function aListListenerAssignsAViewVariable(): void
    {
        $this->setUpTestCase('partnerListPage', [
            'EXT:test_partner_list_events/Configuration/TypoScript/ListMarker.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<p class="test-partner-list-marker">partners-marker|list|3</p>', $content);
        $this->assertStringContainsString('href="/alpha-university"', $content);
    }

    /**
     * A listener that filters after the query replaces the result, and the replacement is what
     * is rendered.
     */
    #[Test]
    public function aListListenerReplacesTheResult(): void
    {
        $this->setUpTestCase('partnerListPage', [
            'EXT:test_partner_list_events/Configuration/TypoScript/ReplaceResult.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="/beta-institute"', $content);
        $this->assertStringNotContainsString('href="/alpha-university"', $content);
        $this->assertStringNotContainsString('href="/delta-regional-college"', $content);
    }
}
