..  _important-geocoding-command-sends-the-referer:

====================================================
Important: The geocoding command identifies the site
====================================================

Description
===========

:bash:`vendor/bin/typo3 academic:geocodepartners <referrer>` geocodes one
partner page per run through the Nominatim API of OpenStreetMap. Its usage
policy asks every application to identify itself, which is what the mandatory
:bash:`referrer` argument is for. The command sent it in a header named
``Referrer``, which is not the HTTP header ``Referer``, so Nominatim never
received it.

*   The argument is sent as the ``Referer`` header now. The argument keeps its
    name, so scheduler tasks and cron jobs need no change.
*   The command reports what it did: the partner it geocoded and the
    coordinates it stored, why geocoding failed, or that no partner is waiting.
    A failed request, an answer that cannot be read and a result that cannot be
    stored are reported as errors on the error output.
*   The scheduler runs a command without any output, so the result is logged as
    well. A partner that could not be geocoded is logged as a warning, which
    the default log configuration of TYPO3 writes. A geocoded partner and an
    empty queue are logged as info. The failures that were logged before still
    are.

Impact
======

An installation that geocodes partner pages identifies itself to Nominatim with
the value it passes. A run from the console shows its result. A scheduled run
leaves the partners it could not geocode in the TYPO3 log.

The geocoded partners appear in the log too once the log level of the command
is lowered to info, for example in :file:`config/system/additional.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['FGTCLB']['AcademicPartners']['Command']['GeocodeCommand']['writerConfiguration'] = [
        \Psr\Log\LogLevel::INFO => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFileInfix' => 'geocoding',
            ],
        ],
    ];

Affected Installations
======================

Installations that run :bash:`academic:geocodepartners`, from the console or as
a scheduler task. Nothing has to be done on update.

..  index:: CLI, ext:academic_partners
