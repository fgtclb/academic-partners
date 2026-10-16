<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Security;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;

/**
 * Allows the tiles of the partner map in `img-src` of the Content Security Policy of the
 * page that is being rendered, and on that page only.
 *
 * The source is derived from the tile URL the map loads its tiles from: its scheme, host
 * and port. A host label with a placeholder, `{s}` for the subdomain in particular,
 * becomes the wildcard `*.`, so `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`
 * allows `https://*.tile.openstreetmap.org`. The path, with its `{z}`, `{x}`, `{y}` or
 * `{r}`, does not take part.
 *
 * The mutation goes to the request scoped `PolicyRegistry` of TYPO3, which the policy of
 * the response applies on top of the mutations of every package and of the site. TYPO3
 * keeps the registered mutations in the page cache with the page, so a cached page
 * restores them.
 *
 * @internal only to be used within `EXT:academic_partners` and not part of public API.
 */
final readonly class MapTileSourceRegistration
{
    /**
     * The tile URL the map module uses when the attribute it reads is empty, see
     * `Resources/Private/TypeScript/frontend/map.ts`.
     */
    public const DEFAULT_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    public function __construct(
        private PolicyRegistry $policyRegistry,
    ) {}

    /**
     * Extends `img-src` by the source of the tile URL. An empty tile URL is the default of
     * the map module. A tile URL that yields no source adds nothing: a relative one is
     * covered by `'self'` already, and a malformed one or one with a scheme other than
     * `http` and `https` loads no tiles a source could allow.
     */
    public function register(string $tileUrl): void
    {
        $source = $this->sourceFor($tileUrl);
        if ($source === null) {
            return;
        }
        $this->policyRegistry->appendMutationCollection(
            new MutationCollection(
                new Mutation(MutationMode::Extend, Directive::ImgSrc, $source),
            ),
        );
    }

    /**
     * The source that allows the tiles of the tile URL, or null where there is none to add.
     *
     * - `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` is `https://*.tile.openstreetmap.org`.
     * - `https://tiles.example.org:8443/{z}/{x}/{y}.png` is `https://tiles.example.org:8443`.
     * - `//{s}.tiles.example.org/{z}/{x}/{y}.png`, without a scheme, is `*.tiles.example.org`,
     *   which the browser matches against the scheme of the page.
     * - A placeholder in a label below the first one, `https://a.{s}.example.org/`, makes
     *   everything up to that label the wildcard, `https://*.example.org`, as a source
     *   cannot express a wildcard inside a host.
     * - A relative URL, a host the wildcard would leave without a dot, a placeholder in
     *   the scheme or the port, a port outside 1 to 65535 and a host that is no valid
     *   host name yield null.
     */
    public function sourceFor(string $tileUrl): ?UriValue
    {
        $tileUrl = trim($tileUrl);
        if ($tileUrl === '') {
            $tileUrl = self::DEFAULT_TILE_URL;
        }
        if (preg_match('#^(?:([A-Za-z][A-Za-z0-9+.\-]*):)?//([^/?\#]*)#', $tileUrl, $matches) !== 1) {
            return null;
        }
        $scheme = strtolower($matches[1]);
        if ($scheme !== '' && $scheme !== 'http' && $scheme !== 'https') {
            return null;
        }
        $authority = $matches[2];
        // A user name and password are no part of a source.
        $atPosition = strrpos($authority, '@');
        if ($atPosition !== false) {
            $authority = substr($authority, $atPosition + 1);
        }
        if (preg_match('#^([^:]+)(?::(\d{1,5}))?$#', $authority, $parts) !== 1) {
            return null;
        }
        $host = $this->hostSource(strtolower($parts[1]));
        if ($host === null) {
            return null;
        }
        $port = '';
        if (isset($parts[2])) {
            // An out of range port is no URI, and UriValue would throw on it.
            if ((int)$parts[2] < 1 || (int)$parts[2] > 65535) {
                return null;
            }
            $port = ':' . (int)$parts[2];
        }

        if ($scheme === '') {
            // A source without a scheme and with a port cannot be told apart from a
            // scheme by the source parser of TYPO3.
            return $port === '' ? new UriValue($host) : null;
        }
        return new UriValue($scheme . '://' . $host . $port);
    }

    /**
     * The host as a source: as it is, or with the labels up to the last one that holds a
     * placeholder replaced by `*`.
     */
    private function hostSource(string $host): ?string
    {
        $labels = explode('.', $host);
        $lastPlaceholder = null;
        foreach ($labels as $index => $label) {
            if (str_contains($label, '{') || str_contains($label, '}')) {
                $lastPlaceholder = $index;
            }
        }
        $wildcard = $lastPlaceholder !== null;
        $domain = implode('.', $wildcard ? array_slice($labels, $lastPlaceholder + 1) : $labels);
        if ($wildcard && !str_contains($domain, '.')) {
            // Nothing left, or a wildcard on a top level domain.
            return null;
        }
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }
        return ($wildcard ? '*.' : '') . $domain;
    }
}
