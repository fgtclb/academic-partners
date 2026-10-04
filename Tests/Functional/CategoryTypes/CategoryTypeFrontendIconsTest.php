<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The category type icons of the partner page, the partner card, the partnerships list
 * and the partnerships teaser come from the frontend icon registry.
 *
 * The fixture `tests/partners-frontend-icons` is a site package. In its
 * `Configuration/FrontendIcons.php` it replaces the icon of the shipped region, and it
 * adds the type `network` with one drawing for the backend (`icon`) and another one for
 * the frontend (`frontendIcon`). Each drawing is a rectangle of its own. The partner type
 * is a shipped type nobody replaces.
 *
 * Alpha University (page 10) carries one category of each of the three types. "/home"
 * lists it, "/partnerships" and "/teaser" show it as a partnership of their page.
 */
final class CategoryTypeFrontendIconsTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const SITE_PACKAGE = 'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    /**
     * The rectangles of the fixture, as the serialisers of both core versions write them.
     */
    private const SITE_REPLACED = 'x="1" y="1" width="2" height="14"';
    private const TYPE_BACKEND = 'x="13" y="1" width="2" height="14"';
    private const TYPE_FRONTEND = 'x="4" y="4" width="8" height="8"';

    /**
     * Parts of the shipped `category-type/region.svg` and `category-type/partner-type.svg`.
     */
    private const SHIPPED_REGION = 'd="M576 112C576 100.9 570.3 90.6';
    private const SHIPPED_PARTNER_TYPE = 'd="M320 64C355.3 64 384 92.7';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/partners-frontend-icons');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypeFrontendIcons.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The partner page is rendered by the site package, the plugins by the page object of
     * the plugin tests, which renders the content of the page and nothing else.
     */
    private function setUpSite(bool $partnerPage): void
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
                    ...($partnerPage ? [self::SITE_PACKAGE] : []),
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    ...($partnerPage ? [] : [self::PLUGIN_RENDERING]),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @return \Generator<string, array{0: string, 1: bool}>
     */
    public static function placeDataProvider(): \Generator
    {
        yield 'partner page' => ['https://www.acme.com/alpha-university', true];
        yield 'partner card of the list' => ['https://www.acme.com/home', false];
        yield 'partnerships list' => ['https://www.acme.com/partnerships', false];
        yield 'partnerships teaser' => ['https://www.acme.com/teaser', false];
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aShippedTypeShowsTheFrontendReplacementOfTheSitePackage(string $url, bool $partnerPage): void
    {
        $this->setUpSite($partnerPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.partners.region');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SITE_REPLACED, $markup);
            $this->assertStringNotContainsString(self::SHIPPED_REGION, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aTypeWithAFrontendIconShowsIt(string $url, bool $partnerPage): void
    {
        $this->setUpSite($partnerPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.partners.network');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::TYPE_FRONTEND, $markup);
            $this->assertStringNotContainsString(self::TYPE_BACKEND, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aShippedTypeNobodyReplacesShowsTheShippedIcon(string $url, bool $partnerPage): void
    {
        $this->setUpSite($partnerPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.partners.partner_type');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SHIPPED_PARTNER_TYPE, $markup);
        }
    }

    /**
     * The backend keeps the drawings the frontend does not show: the declared `icon` of
     * the new type and the shipped region.
     */
    #[Test]
    public function theBackendRegistryKeepsTheDeclaredIcons(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertSame(
            'EXT:test_partners_frontend_icons/Resources/Public/Icons/TypeBackend.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.partners.network')['options']['source'] ?? null,
        );
        $this->assertSame(
            'EXT:academic_partners/Resources/Public/Icons/category-type/region.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.partners.region')['options']['source'] ?? null,
        );
    }

    /**
     * The inner markup of every rendered icon with the identifier, in page order.
     *
     * @return list<string>
     */
    private function renderedIconMarkups(string $content, string $identifier): array
    {
        preg_match_all(
            '@data-identifier="' . preg_quote($identifier, '@') . '" aria-hidden="true">\s*<span class="icon-markup">(.*?)</span>@s',
            $content,
            $matches,
        );

        return $matches[1];
    }
}
