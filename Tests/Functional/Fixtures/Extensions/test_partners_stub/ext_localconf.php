<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Answer every outgoing HTTP request with a canned Nominatim response, so the
// geocoding test never reaches openstreetmap.org. Evaluated identically by v13 and v14.
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = \GuzzleHttp\HandlerStack::create(
    new \TESTS\TestPartnersStub\Http\StubNominatimHandler()
);
