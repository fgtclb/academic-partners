<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A category type a project adds to the group `partners` has no label in the language file
 * of this extension. The frontend names it by the title it is registered with instead,
 * translated into the language of the page, and a label the site sets still wins.
 *
 * The fixture extension `test_partners_titled_category_type` registers `funding_body` with
 * the title "Funding body", "Förderer" in German, and retitles the shipped `region` "Area".
 * Alpha University (page 10) is a European university funded by a federal ministry.
 * "/home" lists it with its filter, "/partnerships" and "/teaser" show it as a partnership
 * of their page. Page 20 and "/de/start" are the German partner page and list.
 */
final class CategoryTypeTitleTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const PARTNER_PAGE = 'https://www.acme.com/alpha-university';
    private const TRANSLATED_PARTNER_PAGE = 'https://www.acme.com/de/alpha-universitaet';
    private const LIST_PAGE = 'https://www.acme.com/home';
    private const TRANSLATED_LIST_PAGE = 'https://www.acme.com/de/start';
    private const PARTNERSHIPS_LIST_PAGE = 'https://www.acme.com/partnerships';
    private const PARTNERSHIPS_TEASER_PAGE = 'https://www.acme.com/teaser';

    private const SITE_PACKAGE = 'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/partners-titled-category-type';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypeTitle.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * Without a site package, the page object of the plugin tests renders the content of
     * the page and nothing else.
     */
    private function setUpSite(bool $partnerPage = false, string $setup = ''): void
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    #[Test]
    public function thePartnerPageNamesTheTypeByTheRegisteredTitle(): void
    {
        $this->setUpSite(partnerPage: true);

        $this->assertSame('Funding body', $this->categoryLabel($this->renderFrontendPage(self::PARTNER_PAGE), 'funding_body'));
    }

    #[Test]
    public function theGermanPartnerPageNamesTheTypeByTheGermanTitle(): void
    {
        $this->setUpSite(partnerPage: true);

        $this->assertSame('Förderer', $this->categoryLabel($this->renderFrontendPage(self::TRANSLATED_PARTNER_PAGE), 'funding_body'));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function itemPageDataProvider(): \Generator
    {
        yield 'partner card of the list' => [self::LIST_PAGE];
        yield 'partnerships list' => [self::PARTNERSHIPS_LIST_PAGE];
        yield 'partnerships teaser' => [self::PARTNERSHIPS_TEASER_PAGE];
    }

    #[DataProvider('itemPageDataProvider')]
    #[Test]
    public function theItemNamesTheTypeByTheRegisteredTitle(string $url): void
    {
        $this->setUpSite();

        $this->assertSame('Funding body', $this->categoryLabel($this->renderFrontendPage($url), 'funding_body'));
    }

    #[Test]
    public function theListFilterNamesTheSelectByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Funding body', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'funding_body'));
    }

    #[Test]
    public function theGermanListFilterNamesTheSelectByTheGermanTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Förderer', $this->selectLabel($this->renderFrontendPage(self::TRANSLATED_LIST_PAGE), 'funding_body'));
    }

    #[Test]
    public function aShippedTypeKeepsTheLabelOfTheExtension(): void
    {
        $this->setUpSite();

        $this->assertSame('Region', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'region'));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function siteLabelDataProvider(): \Generator
    {
        yield 'label of the extension' => ['plugin.tx_academicpartners'];
        yield 'label of the list plugin' => ['plugin.tx_academicpartners_list'];
    }

    #[DataProvider('siteLabelDataProvider')]
    #[Test]
    public function aLabelOfTheSiteWinsOverTheRegisteredTitle(string $path): void
    {
        $this->setUpSite(setup: $path . '._LOCAL_LANG.default.sys_category\.partners\.funding_body = Sponsor');

        $content = $this->renderFrontendPage(self::LIST_PAGE);

        $this->assertSame('Sponsor', $this->selectLabel($content, 'funding_body'));
        $this->assertSame('Sponsor', $this->categoryLabel($content, 'funding_body'));
    }

    #[Test]
    public function aLabelOfTheSiteWinsOnThePartnerPage(): void
    {
        $this->setUpSite(partnerPage: true, setup: 'plugin.tx_academicpartners._LOCAL_LANG.default.sys_category\.partners\.funding_body = Sponsor');

        $this->assertSame('Sponsor', $this->categoryLabel($this->renderFrontendPage(self::PARTNER_PAGE), 'funding_body'));
    }

    /**
     * An empty label counts as none, so a project type whose label a site blanks is named
     * by its title.
     */
    #[Test]
    public function aBlankedLabelOfAProjectTypeFallsBackToTheTitle(): void
    {
        $this->setUpSite(setup: 'plugin.tx_academicpartners._LOCAL_LANG.default.sys_category\.partners\.funding_body =');

        $this->assertSame('Funding body', $this->categoryLabel($this->renderFrontendPage(self::LIST_PAGE), 'funding_body'));
    }

    /**
     * The shipped `partner_type` is registered with the label reference of the extension
     * as its title. On TYPO3 v14 a blanked label falls back to that title, which reads the
     * language file.
     */
    #[Group('not-core-13')]
    #[Test]
    public function aBlankedLabelOfAShippedTypeFallsBackToTheTitle(): void
    {
        $this->setUpSite(setup: 'plugin.tx_academicpartners._LOCAL_LANG.default.sys_category\.partners\.partner_type =');

        $this->assertSame('Partner Type', $this->categoryLabel($this->renderFrontendPage(self::LIST_PAGE), 'partner_type'));
    }

    /**
     * On TYPO3 v13 `LanguageService::sL()` caches the label the lookup resolved, blanked,
     * by the reference that is also the title, so the title is empty as well.
     */
    #[Group('not-core-14')]
    #[Test]
    public function aBlankedLabelOfAShippedTypeStaysEmptyOnTypo3V13(): void
    {
        $this->setUpSite(setup: 'plugin.tx_academicpartners._LOCAL_LANG.default.sys_category\.partners\.partner_type =');

        $this->assertSame('', $this->categoryLabel($this->renderFrontendPage(self::LIST_PAGE), 'partner_type'));
    }

    /**
     * The label in front of the categories of a type, found by the icon of the type that
     * precedes it on the partner page and in every item.
     */
    private function categoryLabel(string $content, string $type): string
    {
        $pattern = '#data-identifier="category_types\.partners\.' . preg_quote($type, '#') . '".*?<b>(.*?)</b>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No categories of the type "%s" are rendered.', $type));
        }
        return rtrim(trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1]))), ':');
    }

    private function selectLabel(string $content, string $selectId): string
    {
        $pattern = '#<label for="' . preg_quote($selectId, '#') . '"[^>]*>(.*?)</label>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No select "%s" is labelled.', $selectId));
        }
        return trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1])));
    }
}
