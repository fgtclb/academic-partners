<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\DataProcessing;

use FGTCLB\AcademicPartners\Security\MapTileSourceRegistration;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Allows the tiles of the partner map in `img-src` while the page around the content
 * element "Partner Map" is generated, see {@see MapTileSourceRegistration}.
 *
 * The map action is not cacheable. TYPO3 v13 prepares the policy of a page it generates
 * before the non-cacheable parts run, so what the action registers reaches only the
 * responses that come from the page cache there. The content element around it is
 * rendered with the page, before the policy is prepared, and TYPO3 stores what it
 * registers with the cached page. TYPO3 v12 builds the policy after the whole page, so
 * the processor is not needed there, and it changes nothing. The extension names the
 * processor for `tt_content.academicpartners_map` in its `ext_localconf.php`, next to the
 * rendering definition of the plugin.
 *
 * The tile URL is the fixed one of the map module. The processor adds no variable.
 *
 * Published, because it has a collaborator and TYPO3 constructs a processor that
 * TypoScript names by its class through the container only when it is public, see
 * "A data processor with a collaborator has to be published" in
 * `docs/architecture/dependency-injection.md`.
 *
 * @internal only to be used within `EXT:academic_partners` and not part of public API.
 */
#[Autoconfigure(public: true)]
#[AutoconfigureTag('data.processor', ['identifier' => 'academic-partners-map-tile-source'])]
final class MapTileSourceProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly MapTileSourceRegistration $mapTileSourceRegistration,
    ) {}

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $this->mapTileSourceRegistration->register(MapTileSourceRegistration::DEFAULT_TILE_URL);
        return $processedData;
    }
}
