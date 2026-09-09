.. _important-partner-map-skips-partners-without-coordinates:

==========================================================
Important: The map leaves out partners without coordinates
==========================================================

Description
===========

:php:`Controller\PartnerController::mapAction()` fed the map from
:php:`Domain\Repository\PartnerRepository::findByDemand()`, which carries no
geocode constraint. A partner page that was never geocoded - or whose geocoding
failed - therefore reached the map template, and :php:`Domain\Model\Partner`
types both coordinates as non-nullable :php:`float` defaulting to `0`:

..  code-block:: php

    protected float $geocodeLongitude = 0;
    protected float $geocodeLatitude = 0;

So a missing coordinate arrived in the template as a perfectly valid one:

..  code-block:: html

    <li class="map-partner" data-lat="0" data-lng="0" …>

:file:`Resources/Private/TypeScript/frontend/map.ts` guarded the values with
:js:`Number.isNaN()`, which `0` passes, and the marker was drawn at 0/0 - open
ocean south of Ghana. The guard could not catch an empty attribute either,
because :js:`Number('')` is `0` and not `NaN`.

The map query now requires both coordinates, and the frontend module refuses to
draw a record whose coordinates it cannot use. A partner without coordinates
stays in the **list**, which is unchanged: it is still a perfectly good list
entry.

Absence is the criterion, not the value. `NULL` and the empty string mean "no
coordinate"; a stored `0` is a real one, and only the pair `0/0` is refused.
Longitude 0 runs through the United Kingdom, France, Spain and Ghana and
latitude 0 is the equator, so treating a single zero as missing would hide
partners that are genuinely there.

The two coordinate columns became :php:`allowLanguageSynchronization`. A place
does not move when the page is translated, so a translation follows its default
record instead of carrying a coordinate of its own. A partner translated before
geocoding ran kept an empty coordinate and was therefore drawn at 0/0 in that
language - and, under the rule above, would have been left off that language's
map entirely.

:php:`Domain\Repository\PartnerRepository::findGeoLocated()` gained the same
requirement, so a record that claims a `successful` or `manually` status without
ever having received coordinates is no longer reported as located.

Impact
======

A partner that was previously drawn at 0/0 disappears from the map. That marker
never pointed at the partner's location, so nothing that pointed anywhere is
lost.

When no partner has coordinates at all, the plugin no longer renders an empty
map centred on Germany. It renders a message instead:

..  code-block:: text

    No partner with a location to show on the map.

The label is `map.noLocatedPartnersFound` in
:file:`Resources/Private/Language/locallang.xlf` and can be overridden like any
other label. Integrators who replaced
:file:`Resources/Private/Templates/Partner/Map.html` keep their own copy and get
neither the condition nor the message.

A template that draws the map for **one** partner is not covered by any of this.
The typical case is a partner detail page that renders the partner's own
location: it has no query to constrain, and the list it hands to the map holds
that single partner whether it has coordinates or not. Such a template draws the
partner at 0/0, or - with the module above - an empty map centred on Germany.

:php:`Domain\Model\Partner::isDrawable()` is the same rule for a single object.
Guard the map with it:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/Pages/AcademicPartner.html

    <f:if condition="{partner.drawable}">
        <f:render partial="Partner/Map" arguments="{partners: '{0: partner}'}" />
    </f:if>

`Partner/Map` stands for the site package's own map partial: this extension
ships the map only as the plugin template, not as a partial.

It is deliberately not called "geo located":
:php:`Domain\Repository\PartnerRepository::findGeoLocated()` also requires a
geocode *status*, and a record can claim to be located without carrying a
coordinate. Like the frontend module it also refuses a coordinate that is not a
finite number.

The model does not see exactly what the query sees, in either direction. An
absent coordinate has already become `0` by the time a template asks, so a pair
with only one coordinate missing reads as drawable at 0 on that axis while the
query excludes it; a hand-entered `0.0` is refused by the model while the query
lets it through. ACE-566 changes the column type and is where the two are
reconciled.

A custom controller that calls
:php:`Domain\Repository\PartnerRepository::findByDemand()` itself gets the old
behaviour unless it opts in, because the demand defaults to `false`:

..  code-block:: php

    $demand->setDrawableOnly(true);

The server-side rule matches the pair `0/0` as a **string**, in the spelling
:php:`Command\GeocodeCommand` writes. A hand-entered `0.0` therefore passes the
query and is dropped by the frontend module instead. A numeric comparison cannot
be expressed portably while the column is `VARCHAR`; ACE-566 changes the column
type and removes that asymmetry.

:php:`Command\GeocodeCommand` writes its result through the DataHandler now
instead of the repository. Only a DataHandler write runs
:php:`DataHandling\Localization\DataMapProcessor`, and geocoding is the one
path that actually writes coordinates - persisting through Extbase would reach
the default record and leave every translation behind, which is the defect one
level down.

Language synchronization takes effect when a record is saved, so it cannot
repair what is already stored. The upgrade wizard
:php:`academicPartners_synchronizePartnerCoordinates` copies each partner's
coordinates onto its translations once. Run it after updating; a site with
translated partner pages needs it, and a site without translations does not.
Deleted translations and workspace versions are left alone, and so is a
coordinate an editor detached from its default record on purpose.

A workspace draft created *before* the update keeps its own stale coordinate:
the wizard does not touch drafts, so publishing such a draft after the update
re-introduces the empty value for that record. Re-saving it in the workspace is
enough to bring it back in line.

Affected Installations
======================

Every installation rendering the :guilabel:`Partners Map` plugin whose partner
records are not all geocoded, and every installation with translated partner
pages. Installations where every partner has coordinates see no change.

Installations whose own templates draw a map for a single partner - most often
a partner detail page - need to add the :php:`isDrawable()` guard shown above;
this extension's own page template renders no map.

.. index:: Database, Frontend, JavaScript, Localization, PHP-API, ext:academic_partners
