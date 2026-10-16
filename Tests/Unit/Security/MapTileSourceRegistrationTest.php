<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Security;

use FGTCLB\AcademicPartners\Security\MapTileSourceRegistration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The source of `img-src` the partner map adds for the tile URL it loads its tiles from
 * (ACE-933).
 */
final class MapTileSourceRegistrationTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function tileUrlsWithASource(): \Generator
    {
        yield 'the default, a subdomain placeholder' => ['https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'https://*.tile.openstreetmap.org'];
        yield 'empty, the default of the map module' => ['', 'https://*.tile.openstreetmap.org'];
        yield 'blank, the default of the map module' => ["  \n", 'https://*.tile.openstreetmap.org'];
        yield 'a host without a placeholder' => ['https://tile.openstreetmap.org/{z}/{x}/{y}.png', 'https://tile.openstreetmap.org'];
        yield 'a retina placeholder in the path' => ['https://tiles.example.org/{z}/{x}/{y}{r}.png?key=abc', 'https://tiles.example.org'];
        yield 'surrounding whitespace' => ['  https://tiles.example.org/{z}/{x}/{y}.png ', 'https://tiles.example.org'];
        yield 'http' => ['http://tiles.example.org/{z}/{x}/{y}.png', 'http://tiles.example.org'];
        yield 'an upper case scheme and host' => ['HTTPS://Tiles.Example.org/{z}/{x}/{y}.png', 'https://tiles.example.org'];
        yield 'a port' => ['https://tiles.example.org:8443/{z}/{x}/{y}.png', 'https://tiles.example.org:8443'];
        yield 'a user name and password' => ['https://user:secret@tiles.example.org/{z}/{x}/{y}.png', 'https://tiles.example.org'];
        yield 'a placeholder inside the first label' => ['https://tiles-{s}.example.org/{z}/{x}/{y}.png', 'https://*.example.org'];
        yield 'a placeholder in a lower label' => ['https://a.{s}.tiles.example.org/{z}/{x}/{y}.png', 'https://*.tiles.example.org'];
        yield 'two placeholder labels' => ['https://{s}.{s}.example.org/x', 'https://*.example.org'];
        yield 'two placeholder labels apart' => ['https://{s}.tiles.{v}.example.org/{z}/{x}/{y}.png', 'https://*.example.org'];
        yield 'the highest port' => ['https://tiles.example.org:65535/{z}/{x}/{y}.png', 'https://tiles.example.org:65535'];
        yield 'a custom placeholder' => ['https://{variant}.tiles.example.org/{z}/{x}/{y}.png', 'https://*.tiles.example.org'];
        yield 'no scheme' => ['//{s}.tiles.example.org/{z}/{x}/{y}.png', '*.tiles.example.org'];
        yield 'no scheme and no placeholder' => ['//tiles.example.org/{z}/{x}/{y}.png', 'tiles.example.org'];
        yield 'an address' => ['https://192.0.2.10/{z}/{x}/{y}.png', 'https://192.0.2.10'];
    }

    #[DataProvider('tileUrlsWithASource')]
    #[Test]
    public function aTileUrlYieldsTheSourceOfItsHost(string $tileUrl, string $expected): void
    {
        $source = (new MapTileSourceRegistration(new PolicyRegistry()))->sourceFor($tileUrl);

        $this->assertInstanceOf(UriValue::class, $source);
        $this->assertSame($expected, (string)$source);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function tileUrlsWithoutASource(): \Generator
    {
        yield 'a path, covered by self' => ['/tiles/{z}/{x}/{y}.png'];
        yield 'a relative path, covered by self' => ['tiles/{z}/{x}/{y}.png'];
        yield 'an unresolved constant' => ['{$plugin.tx_academicpartners.map.tileUrl}'];
        yield 'another scheme' => ['ftp://tiles.example.org/{z}/{x}/{y}.png'];
        yield 'a data URL' => ['data:image/png;base64,AAAA'];
        yield 'no host' => ['https:///{z}/{x}/{y}.png'];
        yield 'the host is a placeholder' => ['https://{s}/{z}/{x}/{y}.png'];
        yield 'a wildcard on a top level domain' => ['https://{s}.org/{z}/{x}/{y}.png'];
        yield 'a placeholder in the top level domain' => ['https://tiles.example.{tld}/{z}/{x}/{y}.png'];
        yield 'a placeholder in the port' => ['https://tiles.example.org:{port}/{z}/{x}/{y}.png'];
        yield 'a placeholder in the scheme' => ['{scheme}://tiles.example.org/{z}/{x}/{y}.png'];
        yield 'an invalid host' => ['https://tiles_example..org/{z}/{x}/{y}.png'];
        yield 'a quote in the host' => ["https://tiles.example.org'/{z}/{x}/{y}.png"];
        yield 'a source list' => ['https://tiles.example.org https://evil.example.com/{z}/{x}/{y}.png'];
        yield 'port zero' => ['https://tiles.example.org:0/{z}/{x}/{y}.png'];
        yield 'a port above the range' => ['https://tiles.example.org:99999/{z}/{x}/{y}.png'];
        yield 'no scheme but a port' => ['//tiles.example.org:8443/{z}/{x}/{y}.png'];
        yield 'an IPv6 address' => ['https://[2001:db8::1]/{z}/{x}/{y}.png'];
    }

    #[DataProvider('tileUrlsWithoutASource')]
    #[Test]
    public function aTileUrlWithoutAHostOfItsOwnYieldsNoSource(string $tileUrl): void
    {
        $this->assertNull((new MapTileSourceRegistration(new PolicyRegistry()))->sourceFor($tileUrl));
    }

    #[Test]
    public function registeringExtendsImageSourcesByTheSourceOfTheTileUrl(): void
    {
        $policyRegistry = new PolicyRegistry();

        (new MapTileSourceRegistration($policyRegistry))->register('https://{s}.tiles.example.org/{z}/{x}/{y}.png');

        $collections = $policyRegistry->getMutationCollections();
        $this->assertCount(1, $collections);
        $mutations = $collections[0]->mutations;
        $this->assertCount(1, $mutations);
        $this->assertInstanceOf(Mutation::class, $mutations[0]);
        $this->assertSame(MutationMode::Extend, $mutations[0]->mode);
        $this->assertSame(Directive::ImgSrc, $mutations[0]->directive);
        $this->assertCount(1, $mutations[0]->sources);
        $this->assertInstanceOf(UriValue::class, $mutations[0]->sources[0]);
        $this->assertSame('https://*.tiles.example.org', (string)$mutations[0]->sources[0]);
    }

    /**
     * `Extend` keeps what `img-src` allows or inherits from `default-src`, and the
     * wildcard allows every subdomain the placeholder stands for.
     */
    #[Test]
    public function theRegisteredSourceAllowsTheTilesNextToWhatThePolicyAllowedBefore(): void
    {
        $policyRegistry = new PolicyRegistry();
        (new MapTileSourceRegistration($policyRegistry))->register('');

        $policy = (new Policy())
            ->default(new UriValue('https://cdn.example.org'))
            ->mutate(...$policyRegistry->getMutationCollections());

        $this->assertTrue($policy->containsDirective(Directive::ImgSrc, new UriValue('https://cdn.example.org')));
        $this->assertTrue($policy->coversDirective(Directive::ImgSrc, new UriValue('https://a.tile.openstreetmap.org')));
        $this->assertFalse($policy->coversDirective(Directive::ImgSrc, new UriValue('https://a.tile.example.org')));
    }

    #[DataProvider('tileUrlsWithoutASource')]
    #[Test]
    public function aTileUrlWithoutASourceRegistersNothing(string $tileUrl): void
    {
        $policyRegistry = new PolicyRegistry();

        (new MapTileSourceRegistration($policyRegistry))->register($tileUrl);

        $this->assertFalse($policyRegistry->hasMutationCollections());
    }
}
