<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\WorkspacePublishingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * A page and a partner role edited in a workspace and published still show the
 * partnership under its role in the partnerships list (ACE-907).
 *
 * Publishing used to leave the partnership pointing at the deleted version row of its page:
 * published from live, as the scheduled publishing of a workspace does, and published
 * from the workspace module as well, because both the page and the role were edited in
 * the workspace. The list showed nothing.
 */
final class PartnershipsWorkspacePublishTest extends AbstractAcademicPartnersTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;
    use WorkspacePublishingTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addCoreExtensionsToLoad('typo3/cms-workspaces');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PartnershipsWorkspacePublish/partnershipsListPage.csv');
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
        ]);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function publishingPlaces(): array
    {
        return [
            'published from the workspace' => [true],
            'published from live' => [false],
        ];
    }

    #[Test]
    #[DataProvider('publishingPlaces')]
    public function thePartnershipsListShowsThePartnershipOfAPageAndARolePublishedFromAWorkspace(bool $fromTheWorkspace): void
    {
        $this->runDataHandlerInWorkspace(1, [
            'pages' => [2 => ['title' => 'Home (changed)']],
            'tx_academicpartners_domain_model_role' => [1 => ['name' => 'Research partner (changed)']],
        ]);
        $this->publishWorkspace(1, $fromTheWorkspace);
        unset($GLOBALS['BE_USER']);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('Home (changed)', $content);
        $this->assertStringContainsString('Alpha University', $content);
        $this->assertStringContainsString('Research partner (changed)', $content);
    }
}
