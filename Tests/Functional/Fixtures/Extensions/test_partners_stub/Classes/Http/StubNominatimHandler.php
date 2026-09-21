<?php

declare(strict_types=1);

namespace TESTS\TestPartnersStub\Http;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle handler answering every request with a canned Nominatim result, so the
 * geocoding functional test performs no outgoing HTTP request.
 *
 * Stubbed at handler level for the same reason as `test_bitejobs_stub`: the request
 * factory cannot be subclassed identically for both supported core versions, and the
 * handler slot is configuration rather than API.
 */
final class StubNominatimHandler
{
    public const LATITUDE = '52.520008';
    public const LONGITUDE = '13.404954';

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        return Create::promiseFor(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string)json_encode([
                    [
                        'lat' => self::LATITUDE,
                        'lon' => self::LONGITUDE,
                        'display_name' => 'Stubbed place',
                    ],
                ]),
            )
        );
    }
}
