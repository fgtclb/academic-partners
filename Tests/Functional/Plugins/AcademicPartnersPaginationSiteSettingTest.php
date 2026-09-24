<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetRegistry;

/**
 * The number of page links as a site setting of the set `fgtclb/academic-partners-list`,
 * on a site that has no TypoScript record of the extension - with numbered pagination,
 * the only one that reads it.
 */
final class AcademicPartnersPaginationSiteSettingTest extends AbstractAcademicPartnersPaginationTestCase
{
    private const LIST_SET = 'fgtclb/academic-partners-list';
    private const SETTING = 'plugin.tx_academicpartners.pagination.numberOfLinks';

    protected function setUp(): void
    {
        $this->addTestExtensionsToLoad('georgringer/numbered-pagination');
        parent::setUp();
    }

    /**
     * Each test writes a site of its own.
     */
    protected function setUpPaginationSite(): void {}

    #[Test]
    public function theListSetDeclaresTheSettingWithTheDefaultOfTheConstant(): void
    {
        $definitions = [];
        foreach ($this->get(SetRegistry::class)->getSet(self::LIST_SET)?->settingsDefinitions ?? [] as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $this->assertArrayHasKey(self::SETTING, $definitions);
        $this->assertSame('int', $definitions[self::SETTING]->type);
        $this->assertSame(5, $definitions[self::SETTING]->default);

        // A site that reads the static template after the set gets the constant's default
        // back, so the two have to agree.
        $constants = (string)file_get_contents(__DIR__ . '/../../../Configuration/TypoScript/constants.typoscript');
        $this->assertMatchesRegularExpression('/^\s*pagination \{\s*(#[^\n]*\s*)*numberOfLinks = 5$/m', $constants);
    }

    #[Test]
    public function aSiteSettingLimitsThePageLinks(): void
    {
        $this->setUpSiteSetSite([self::SETTING => 2]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Alpha University', 'Bravo Institute'], $this->renderedPartners($content));
        $this->assertSame(['1', '2', '…', 'next', 'last'], $this->paginationLabels($content));
    }

    /**
     * Six pages, one partner each: five of them are linked, the sixth is behind the
     * ellipsis.
     */
    #[Test]
    public function withoutASiteSettingFivePagesAreLinked(): void
    {
        $this->setUpSiteSetSite([]);

        $content = $this->renderFrontendPage('https://www.acme.com/one-per-page');

        $this->assertSame(['Alpha University'], $this->renderedPartners($content));
        $this->assertSame(['1', '2', '3', '4', '5', '…', 'next', 'last'], $this->paginationLabels($content));
    }

    /**
     * The numbered pagination would take ten links for a number below one - here, every
     * page. The list takes the default instead.
     */
    #[Test]
    public function aSiteSettingOfZeroFallsBackToFiveLinks(): void
    {
        $this->setUpSiteSetSite([self::SETTING => 0]);

        $content = $this->renderFrontendPage('https://www.acme.com/one-per-page');

        $this->assertSame(['1', '2', '3', '4', '5', '…', 'next', 'last'], $this->paginationLabels($content));
    }

    /**
     * @param array<string, int> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        // The page object only; "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => '@import \'EXT:academic_partners/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', self::LIST_SET],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }
}
