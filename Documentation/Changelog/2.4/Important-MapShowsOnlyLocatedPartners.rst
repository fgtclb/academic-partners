..  _important-map-shows-only-located-partners:

======================================================
Important: The map shows only partners with a location
======================================================

Description
===========

A partner page whose geocoding never ran, or failed, was drawn on the map at
0/0 - open ocean south of Ghana - instead of being left out.

:php:`PartnerController::mapAction()` fed the map from
:php:`PartnerRepository::findByDemand()`, which carried no geocode constraint,
so such a record reached the template. :php:`Domain\Model\Partner` types both
coordinates as non-nullable :php:`float` defaulting to :php:`0`, so a missing
value arrived there as a valid one and rendered as
:html:`data-lat="0" data-lng="0"`.

Four things changed:

*   The map query requires both coordinates. **The list is unchanged**: a
    partner without coordinates is still a perfectly good list entry.
*   With nothing left to draw, the plugin renders a message instead of an empty
    canvas centred on Germany, and the Leaflet assets are no longer loaded for a
    map that is not there.
*   :php:`Partner::isDrawable()` is the same rule for a template that renders
    one partner rather than a query result - a detail page drawing the partner's
    own location has no query to constrain.
*   The coordinate columns became :php:`allowLanguageSynchronization`. A place
    does not move when the page is translated, and a partner translated before
    geocoding ran kept an empty coordinate and was drawn at 0/0 in that
    language.

**Absence is the criterion, not the value.** :sql:`NULL` and the empty string
mean "no coordinate"; a stored :sql:`0` is a real one, and only the pair 0/0 is
refused. Longitude 0 runs through the United Kingdom, France, Spain and Ghana,
and latitude 0 is the equator, so treating a single zero as missing would hide
partners that are genuinely there.

Impact
======

Synchronization acts on the write path only, so it repairs nothing that is
already stored. The upgrade wizard
:guilabel:`Synchronize academic partner coordinates with their translations`
does that once for existing records. It leaves alone a translation whose
coordinates the editor detached deliberately, and deleted or workspace rows.

:php:`GeocodeCommand` writes through the DataHandler now rather than through the
repository, because only a DataHandler write runs the synchronization. A refused
write fails the command, where it previously passed silently: the queue selects
on :php:`geocode_status = 'open'`, so a result that never reached the database
would be retried on every scheduled run.

Affected Installations
======================

Every installation that renders the partner map plugin. Run the upgrade wizard
once after updating.

An installation that replaced :file:`Partner/Map.html` keeps its own copy and
gets neither the empty state nor the conditional assets; the query change
reaches it regardless.

..  note::

    The pair 0/0 is matched as a string, in the spelling the geocoding command
    writes, because a numeric comparison is not portable while the column is
    :sql:`VARCHAR(20)`. A hand-entered :php:`0.0` therefore reaches the
    frontend, which drops it - see
    :ref:`Important: The partner map skips partners without coordinates
    <important-partner-map-skips-partners-without-coordinates>`.

.. index:: Backend, Frontend, Database, ext:academic_partners
