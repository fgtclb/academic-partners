<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Pages;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders a page of the page type this extension registers, on a site package that
 * derives the Fluid template name from the backend layout.
 *
 * That derivation is what the shipped page template has to survive, and it does not
 * survive it by itself: "case = uppercamelcase" lowercases the whole string before
 * camel casing it, so the registered layout "pagets__AcademicPartner" arrives as "Academicpartner"
 * and Fluid finds no such file. The extension therefore sets "page.10.templateName"
 * inside its own page type condition, and this test is what keeps it set.
 *
 * Remove those two lines from "Configuration/TypoScript/Page/AcademicPartners.typoscript" and the page
 * renders the site package's fallback template instead - which is what the second
 * assertion is for.
 *
 * The category tests pin what the template renders of the categories assigned to the
 * page. That block read a property the model does not have until ACE-673, so it never
 * appeared; asserting the rendered output rather than the property name is what keeps a
 * rename from hiding it again.
 *
 * The page record tests pin where the data processor takes the page record from, on a
 * PAGEVIEW and on a FLUIDTEMPLATE page object.
 */
final class AcademicPartnerPageTemplateTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const FIXTURES = 'EXT:academic_partners/Tests/Functional/Pages/Fixtures/TypoScript/Setup/';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string $sitePackage A file below "Fixtures/TypoScript/Setup/", included before the extension.
     * @param list<string> $additionalSetup Files below "Fixtures/TypoScript/Setup/", included after it.
     */
    private function setUpTestCase(string $sitePackage = 'SitePackage.typoscript', array $additionalSetup = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPartnerPageTemplateTest/page.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_partners/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    // The site package first, the extension after it - see the fixture.
                    self::FIXTURES . $sitePackage,
                    'EXT:academic_partners/Configuration/TypoScript/setup.typoscript',
                    // The page template of this page type renders
                    // "styles.content.getContent" through "f:cObject", and that ViewHelper
                    // throws when the path is undefined. The override that defines it is a
                    // component of its own since 2.4, so a site that renders this page type
                    // has to include it - which is what this line is.
                    'EXT:academic_partners/Configuration/TypoScript/ContentLoad/setup.typoscript',
                    ...array_map(static fn(string $file): string => self::FIXTURES . $file, $additionalSetup),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    #[Test]
    public function pageTemplateIsResolvedOnASitePackageDerivingTheNameFromTheBackendLayout(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('academic-partners-detail', $content);
        $this->assertStringNotContainsString('site-package-default-template', $content);
    }

    /**
     * The fixture assigns one category of type "region" and one of type "partner_type" to
     * the page, so both type labels and both category titles have to reach the output.
     */
    #[Test]
    public function partnerPageListsItsCategoriesGroupedByType(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('Region', $content);
        $this->assertStringContainsString('Rhine-Main Area', $content);
        $this->assertStringContainsString('Partner Type', $content);
        $this->assertStringContainsString('Research Institute', $content);
    }

    /**
     * "collaboration_type" is a registered type and the fixture even holds a category of it,
     * but that category is assigned to no page. Neither the type nor its category may show up.
     */
    #[Test]
    public function partnerPageOmitsACategoryTypeWithoutAnAssignedCategory(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringNotContainsString('Collaboration Type', $content);
        $this->assertStringNotContainsString('Joint Degree Programme', $content);
    }

    #[Test]
    public function partnerPageWithoutCategoriesRendersNoCategoryList(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/acme-ag');

        $this->assertStringContainsString('academic-partners-detail', $content);
        $this->assertStringNotContainsString('Region', $content);
        $this->assertStringNotContainsString('Rhine-Main Area', $content);
        $this->assertStringNotContainsString('Partner Type', $content);
        $this->assertStringNotContainsString('Research Institute', $content);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sitePackageDataVariableDataProvider(): \Generator
    {
        yield 'a text' => ['SitePackageDataVariable.typoscript'];
        yield 'the records of a query' => ['SitePackageDataRecords.typoscript'];
    }

    /**
     * PAGEVIEW reserves "page" but not "data", so a site package may assign a "data" of
     * its own. The data processor reads the page record from "page" first, and the
     * heading, which comes from the partner it builds, still shows. The records of a
     * query are an array as well, so checking the type of "data" alone would not find
     * the page record.
     */
    #[Test]
    #[DataProvider('sitePackageDataVariableDataProvider')]
    #[Group('not-core-12')]
    public function partnerPageReadsThePageRecordFromPageWhenAPageViewSitePackageAssignsData(string $dataVariable): void
    {
        $this->setUpTestCase('SitePackagePageView.typoscript', [$dataVariable]);

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('<h1>web-vision GmbH</h1>', $content);
    }

    /**
     * FLUIDTEMPLATE does not reserve "page", so a site package may assign a "page" of its
     * own. Only an object with "getPageRecord()" counts as "page", anything else leaves
     * the page record to "data".
     */
    #[Test]
    public function partnerPageReadsThePageRecordFromDataWhenAFluidTemplateSitePackageAssignsPage(): void
    {
        $this->setUpTestCase(additionalSetup: ['SitePackagePageVariable.typoscript']);

        $content = $this->renderFrontendPage('https://www.acme.com/web-vision');

        $this->assertStringContainsString('<h1>web-vision GmbH</h1>', $content);
    }
}
