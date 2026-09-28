..  _important-partner-map-honours-show-on-map:

================================================
Important: The partner map honours "Show on map"
================================================

Description
===========

A partner page has the switch :guilabel:`Show on map`, next to its coordinates,
on by default. The partner map ignored it: it drew every partner with coordinates
that its filter matched, whatever the switch said.

*   The :guilabel:`Partners Map` content element leaves out a partner whose
    :guilabel:`Show on map` is switched off.
*   :php:`Domain\Model\Partner::isShownOnMap()`, :html:`{partner.shownOnMap}` in
    Fluid, is the same rule for a template that renders a map for one partner:
    the partner has coordinates to draw and the switch is on.
*   The map reads the switch of the partner page in the language it is rendered
    in. The switch of a translation now follows its default record, as the
    coordinates do, and an editor can detach it for one language.
*   The partner list and the partnerships elements are not affected.

Impact
======

A site where editors switched partners off sees them disappear from its maps
after the update. These are the live partner pages it concerns, in every
language:

..  code-block:: sql

    SELECT uid, sys_language_uid, title FROM pages
    WHERE doktype = 40 AND show_on_map = 0 AND deleted = 0 AND t3ver_wsid = 0;

A translation can hold a different value from its default record, since nothing
read the switch before: one made while the default record was switched off
still holds that `0`. The upgrade wizard :guilabel:`Synchronize academic partner
pages with their translations`, which synchronizes the coordinates as well, now
copies the switch of the default record onto every translation that is not
detached, once. Run it after updating.

A template of a site package that renders the map for one partner and guards it
with :html:`{partner.drawable}` still draws a partner whose switch is off. Guard
it with :html:`{partner.shownOnMap}` instead.

.. index:: Frontend, TCA, ext:academic_partners
