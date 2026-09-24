<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Tca;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\PluginFlexFormDataStructureTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guards the FlexForm data structure of the plugins against a shape that only
 * works on one of the supported core versions.
 *
 * @see PluginFlexFormDataStructureTrait
 */
final class PluginFlexFormTest extends AbstractAcademicPartnersTestCase
{
    use PluginFlexFormDataStructureTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pluginContentTypeDataProvider(): \Generator
    {
        yield 'Partner list' => ['academicpartners_list'];
        yield 'Partner map' => ['academicpartners_map'];
    }

    #[Test]
    #[DataProvider('pluginContentTypeDataProvider')]
    public function pluginFlexFormIsResolvedForContentType(string $cType): void
    {
        $this->assertPluginFlexFormIsResolved($cType);
    }

    #[Test]
    public function listOffersPaginationOnASheetOfItsOwn(): void
    {
        $this->assertPluginFlexFormIsResolved('academicpartners_list', 'pagination');
        $this->assertSame(
            ['settings.paginationEnabled', 'settings.pagination.resultsPerPage'],
            $this->fieldNames('academicpartners_list', 'pagination'),
        );
    }

    /**
     * What an element gets that nobody configured: no pagination, and ten partners a
     * page once it is switched on. A results per page below one is not accepted.
     */
    #[Test]
    public function paginationFieldsDefaultToOffAndTen(): void
    {
        $fields = $this->resolvePluginFlexFormDataStructure('academicpartners_list')['sheets']['pagination']['ROOT']['el'] ?? [];

        $this->assertSame('0', (string)($fields['settings.paginationEnabled']['config']['default'] ?? null));
        $this->assertSame('10', (string)($fields['settings.pagination.resultsPerPage']['config']['default'] ?? null));
        $this->assertSame('1', (string)($fields['settings.pagination.resultsPerPage']['config']['range']['lower'] ?? null));
    }

    /**
     * The map draws every partner the filter matches, so a pagination field on it
     * would be a switch an editor turns on and sees nothing happen.
     */
    #[Test]
    public function mapOffersNoPagination(): void
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure('academicpartners_map');

        $this->assertSame(['sDEF'], array_keys($dataStructure['sheets'] ?? []));
        foreach ($this->fieldNames('academicpartners_map', 'sDEF') as $fieldName) {
            $this->assertStringNotContainsString('pagination', strtolower($fieldName));
        }
    }

    /**
     * List and map carry the filter fields in two files since the list gained its
     * pagination sheet. This keeps the two from drifting apart.
     */
    #[Test]
    public function listAndMapOfferTheSameFilterFields(): void
    {
        $this->assertSame(
            $this->resolvePluginFlexFormDataStructure('academicpartners_list')['sheets']['sDEF'] ?? null,
            $this->resolvePluginFlexFormDataStructure('academicpartners_map')['sheets']['sDEF'] ?? null,
        );
    }

    /**
     * @return list<string>
     */
    private function fieldNames(string $cType, string $sheetName): array
    {
        $dataStructure = $this->resolvePluginFlexFormDataStructure($cType);

        return array_map(strval(...), array_keys($dataStructure['sheets'][$sheetName]['ROOT']['el'] ?? []));
    }
}
