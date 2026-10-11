..  _important-partner-map-honours-show-on-map:

================================================
Important: The partner map honours "Show on map"
================================================

Description
===========

A partner page has the switch :guilabel:`Show on map` next to its coordinates,
on by default. The partner map ignored it: it drew every partner with
coordinates that its filter matched, whatever the switch said.

*   The :guilabel:`Partners Map` content element leaves out a partner whose
    :guilabel:`Show on map` is switched off.
*   :php:`Domain\Model\Partner::isShownOnMap()`, :html:`{partner.shownOnMap}` in
    Fluid, is the same rule for a template that renders a map for one partner:
    the partner has coordinates to draw and the switch is on.
*   The map reads the switch of the partner page in the language it is rendered
    in. The switch of a translation now follows its default record, as the
    coordinates do, and an editor can detach it for one language.
*   The partner list and the partnership content elements are not affected.

Impact
======

A site where editors switched partners off sees them disappear from its maps
after the update. These are the live partner pages it concerns, in every
language:

..  code-block:: sql

    SELECT uid, sys_language_uid, title FROM pages
    WHERE doktype = 40 AND show_on_map = 0 AND deleted = 0 AND t3ver_wsid = 0;

A translation can hold a different value from its default record, since nothing
read the switch before. One made while the default record was switched off
still holds that :sql:`0`. The upgrade wizard :guilabel:`Synchronize academic
partner pages with their translations`, which synchronizes the coordinates as
well, copies the switch of the default record onto every translation that is
not detached, once.

A template of a site package that renders the map for one partner guards it
with :html:`{partner.shownOnMap}`. :html:`{partner.drawable}`, which this
version adds as well, checks the coordinates only and ignores the switch.

Affected Installations
======================

Installations that render the partner map plugin and have partner pages with
:guilabel:`Show on map` switched off, in any language.

Migration
=========

Run the upgrade wizard once after updating, in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPartners_synchronizePartnerTranslations

..  index:: Frontend, TCA, ext:academic_partners
