<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\DataProcessing;

use FGTCLB\AcademicPartners\Security\MapTileSourceRegistration;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Allows the tiles of the partner map in `img-src` while the page around the content
 * element "Partner Map" is generated, see {@see MapTileSourceRegistration}.
 *
 * The map action is not cacheable, so it renders after TYPO3 has prepared the policy of
 * a page it generates, and what it registers reaches only the responses that come from
 * the page cache. The content element around it is rendered with the page, before the
 * policy is prepared, and TYPO3 stores what it registers with the cached page. The
 * extension names the processor for `tt_content.academicpartners_map` in its
 * `ext_localconf.php`, next to the rendering definition of the plugin.
 *
 * The tile URL is the one the map action renders, in the order Extbase merges the
 * settings: `settings.map.tileUrl` of `plugin.tx_academicpartners_map`, which Extbase
 * merges last, over the one of the plugin object `20` of the content element, over the
 * one of `plugin.tx_academicpartners`.
 * The processor adds no variable.
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
final readonly class MapTileSourceProcessor implements DataProcessorInterface
{
    public function __construct(
        private MapTileSourceRegistration $mapTileSourceRegistration,
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
        $this->mapTileSourceRegistration->register($this->tileUrl($cObj, $contentObjectConfiguration));
        return $processedData;
    }

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     */
    private function tileUrl(ContentObjectRenderer $cObj, array $contentObjectConfiguration): string
    {
        $plugins = [];
        $typoScript = $cObj->getRequest()->getAttribute('frontend.typoscript');
        if ($typoScript instanceof FrontendTypoScript && $typoScript->hasSetup()) {
            $plugins = $typoScript->getSetupArray()['plugin.'] ?? [];
        }
        $tileUrl = $plugins['tx_academicpartners_map.']['settings.']['map.']['tileUrl']
            ?? $contentObjectConfiguration['20.']['settings.']['map.']['tileUrl']
            ?? $plugins['tx_academicpartners.']['settings.']['map.']['tileUrl']
            ?? '';
        return is_string($tileUrl) ? $tileUrl : '';
    }
}
