..  _important-partner-map-skips-partners-without-coordinates:

=============================================================
Important: The partner map skips partners without coordinates
=============================================================

Description
===========

A partner that was never geocoded was drawn at 0/0 - open ocean off the coast of
Africa, several hundred kilometres from anything.

The module read the coordinates with :js:`Number(partner.dataset.lat)` and
skipped the partner when the result was :js:`NaN`. :js:`Number('')` is :js:`0`
and not :js:`NaN`, so an absent coordinate walked straight through that guard.

Absence is tested on the raw attribute now, before the conversion that hides it,
and the pair 0/0 is refused whatever spelling it arrives in - it means "nothing
was written" rather than a place. A **single** zero is kept: longitude 0 runs
through the United Kingdom, France, Spain and Ghana, and latitude 0 is the
equator.

A partner that is skipped is still reported on the browser console, as before:
an unusable record is an editorial mistake, and silence would make it invisible.

Impact
======

The map shows the partners that have a location and no longer invents one for
the others. An installation whose partners are all geocoded sees no difference.

Affected Installations
======================

Every installation that renders the partner map plugin with partners that carry
no coordinates. Nothing has to be done on update.

..  note::

    This is the client side. A partner without coordinates is still delivered to
    the page and still counted in the partner list; only the map no longer draws
    it. Filtering such a partner out of the query, and a wizard that fills the
    coordinates of existing records, exist on the 3.x line and are not part of
    this release.

.. index:: Frontend, JavaScript, ext:academic_partners
