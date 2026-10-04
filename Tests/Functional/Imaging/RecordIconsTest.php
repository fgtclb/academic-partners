<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicPartnersTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;

    private const DOKTYPE_ICON = 'tx-academicpartners-doktype-partner';
    private const PLUGIN_ICON = 'tx-academicpartners-plugin-partners';
    private const LIST_PLUGIN_ICON = 'tx-academicpartners-plugin-list';
    private const MAP_PLUGIN_ICON = 'tx-academicpartners-plugin-map';
    private const TEASER_PLUGIN_ICON = 'tx-academicpartners-plugin-partnerships-teaser';
    private const PARTNERSHIP_ICON = 'tx-academicpartners-record-partnership';
    private const ROLE_ICON = 'tx-academicpartners-record-role';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            self::DOKTYPE_ICON,
            self::PLUGIN_ICON,
            self::LIST_PLUGIN_ICON,
            self::MAP_PLUGIN_ICON,
            self::TEASER_PLUGIN_ICON,
            self::PARTNERSHIP_ICON,
            self::ROLE_ICON,
            'category_types.partners.region',
            'category_types.partners.partner_type',
            'category_types.partners.collaboration_type',
            'category_types.partners.sdg',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function categoryTypeIconIdentifiers(): \Generator
    {
        foreach (self::recordIconIdentifiers() as $name => $arguments) {
            if (str_starts_with($arguments[0], 'category_types.')) {
                yield $name => $arguments;
            }
        }
    }

    /**
     * The icons of `Configuration/Icons.php` with the file each one is drawn from. The page
     * type and the content element of the partners linked to a page share one drawing, the
     * two record icons use the shared drawings of academic_base.
     *
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function backendIconSources(): \Generator
    {
        yield self::DOKTYPE_ICON => [self::DOKTYPE_ICON, 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg'];
        yield self::PLUGIN_ICON => [self::PLUGIN_ICON, 'EXT:academic_partners/Resources/Public/Icons/plugin/partners.svg'];
        yield self::LIST_PLUGIN_ICON => [self::LIST_PLUGIN_ICON, 'EXT:academic_partners/Resources/Public/Icons/plugin/list.svg'];
        yield self::MAP_PLUGIN_ICON => [self::MAP_PLUGIN_ICON, 'EXT:academic_partners/Resources/Public/Icons/plugin/map.svg'];
        yield self::TEASER_PLUGIN_ICON => [self::TEASER_PLUGIN_ICON, 'EXT:academic_partners/Resources/Public/Icons/plugin/partnerships-teaser.svg'];
        yield self::PARTNERSHIP_ICON => [self::PARTNERSHIP_ICON, 'EXT:academic_base/Resources/Public/Icons/info/partnership.svg'];
        yield self::ROLE_ICON => [self::ROLE_ICON, 'EXT:academic_base/Resources/Public/Icons/info/role.svg'];
    }

    /**
     * The identifiers of 2.x. They are renamed without an alias, so neither registry may
     * still answer them.
     *
     * @return \Generator<string, array{0: string}>
     */
    public static function removedIconIdentifiers(): \Generator
    {
        yield 'academic-partners' => ['academic-partners'];
        yield 'tx_academicpartners_domain_model_partnership' => ['tx_academicpartners_domain_model_partnership'];
        yield 'tx_academicpartners_domain_model_role' => ['tx_academicpartners_domain_model_role'];
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function recordTypeIcons(): \Generator
    {
        yield 'academic partner page type' => ['pages', '40', self::DOKTYPE_ICON];
        yield 'partner list' => ['tt_content', 'academicpartners_list', self::LIST_PLUGIN_ICON];
        yield 'partner map' => ['tt_content', 'academicpartners_map', self::MAP_PLUGIN_ICON];
        yield 'partnerships list' => ['tt_content', 'academicpartners_partnershipslist', self::PLUGIN_ICON];
        yield 'partnerships teaser' => ['tt_content', 'academicpartners_partnershipsteaser', self::TEASER_PLUGIN_ICON];
        yield 'partnership' => ['tx_academicpartners_domain_model_partnership', 'default', self::PARTNERSHIP_ICON];
        yield 'role' => ['tx_academicpartners_domain_model_role', 'default', self::ROLE_ICON];
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function typeSelectItemIcons(): \Generator
    {
        yield 'academic partner page type' => ['pages', 'doktype', '40', self::DOKTYPE_ICON];
        yield 'partner list' => ['tt_content', 'CType', 'academicpartners_list', self::LIST_PLUGIN_ICON];
        yield 'partner map' => ['tt_content', 'CType', 'academicpartners_map', self::MAP_PLUGIN_ICON];
        yield 'partnerships list' => ['tt_content', 'CType', 'academicpartners_partnershipslist', self::PLUGIN_ICON];
        yield 'partnerships teaser' => ['tt_content', 'CType', 'academicpartners_partnershipsteaser', self::TEASER_PLUGIN_ICON];
    }

    /**
     * The frontend renders the category type icons too, from the frontend icon registry,
     * with the file and the provider of the backend.
     */
    #[Test]
    #[DataProvider('categoryTypeIconIdentifiers')]
    public function categoryTypeIconIsTheSameFrontendIcon(string $identifier): void
    {
        $this->assertIconIsRegisteredInBothRegistries($identifier);
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * A `doktype`, `plugin` or `record` icon is shown by the backend only, so it lives in
     * the backend registry only. A site package replacing it there must not find a second
     * registration in the frontend registry that keeps the shipped drawing.
     */
    #[Test]
    #[DataProvider('backendIconSources')]
    public function backendIconIsDrawnFromItsFileAndIsNoFrontendIcon(string $identifier, string $source): void
    {
        $this->assertSame(
            $source,
            $this->get(IconRegistry::class)->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? null,
            sprintf('Icon "%s" is not drawn from its file.', $identifier),
        );
        $this->assertFalse(
            $this->get(FrontendIconRegistry::class)->isRegistered($identifier),
            sprintf('Icon "%s" is registered in the frontend icon registry as well.', $identifier),
        );
    }

    #[Test]
    #[DataProvider('removedIconIdentifiers')]
    public function identifierOfTheEarlierVersionIsNotRegisteredAnyMore(string $identifier): void
    {
        $this->assertFalse(
            $this->get(IconRegistry::class)->isRegistered($identifier),
            sprintf('Icon "%s" is still registered in the icon registry of the backend.', $identifier),
        );
        $this->assertFalse(
            $this->get(FrontendIconRegistry::class)->isRegistered($identifier),
            sprintf('Icon "%s" is registered in the frontend icon registry.', $identifier),
        );
    }

    /**
     * A registered identifier nothing points at is as invisible as a missing one: the page
     * tree, the page module and the record list read `typeicon_classes`, and nothing else.
     */
    #[Test]
    #[DataProvider('recordTypeIcons')]
    public function recordTypeResolvesToItsIcon(string $table, string $type, string $identifier): void
    {
        $this->assertSame(
            $identifier,
            $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] ?? null,
            sprintf('%s.ctrl.typeicon_classes.%s does not name the icon of this extension.', $table, $type),
        );
    }

    /**
     * The type selects are a channel of their own: the doktype select of the page
     * properties and the CType select of a content element show the icon of their item,
     * which is written independently of `typeicon_classes`.
     */
    #[Test]
    #[DataProvider('typeSelectItemIcons')]
    public function typeSelectItemCarriesItsIcon(string $table, string $field, string $value, string $identifier): void
    {
        $icons = [];
        foreach ($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] ?? [] as $item) {
            if ((string)($item['value'] ?? '') === $value) {
                $icons[] = $item['icon'] ?? null;
            }
        }

        $this->assertSame([$identifier], $icons, sprintf('The %s.%s item "%s" does not carry its icon.', $table, $field, $value));
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_partners');
    }
}
