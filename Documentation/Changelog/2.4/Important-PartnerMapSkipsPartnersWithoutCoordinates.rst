..  _important-partner-map-skips-partners-without-coordinates:

=============================================================
Important: The partner map skips partners without coordinates
=============================================================

Description
===========

A partner that was never geocoded was drawn at 0/0, in the open ocean off the
coast of Africa, several hundred kilometres from anything.

The map script skipped a partner only when a coordinate was not a number. A
partner without coordinates reached the page as :html:`data-lat="0"`, because
the model types the coordinates as :php:`float` defaulting to :php:`0`, or as
an empty attribute from a template override. Both pass that test, since the
empty string converts to :js:`0` as well.

Absence is tested on the raw attribute now, before the conversion that hides
it, and the pair 0/0 is refused whatever spelling it arrives in. It means
"nothing was written" rather than a place. A **single** zero is kept:
longitude 0 runs through the United Kingdom, France, Spain and Ghana, and
latitude 0 is the equator.

A partner that is skipped is still reported on the browser console, as before.
An unusable record is an editorial mistake, and silence would make it
invisible.

Impact
======

The map shows the partners that have a location and no longer invents one for
the others. An installation whose partners are all geocoded sees no difference.

Affected Installations
======================

Every installation that renders the partner map plugin with partners that carry
no coordinates. Nothing has to be done on update.

..  note::

    This is the client side. The query leaves such a partner out of the map as
    well, and an upgrade wizard fills the coordinates of existing translations,
    see :ref:`important-map-shows-only-located-partners`. The partner list still
    lists it.

..  index:: Frontend, JavaScript, ext:academic_partners
