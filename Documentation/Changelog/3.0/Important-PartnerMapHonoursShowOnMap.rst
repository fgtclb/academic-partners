.. _important-ace-770-academic-partners-map-partial:

===========================================================
Important: The map partial skips a partner it must not draw
===========================================================

Description
===========

The partial :file:`Partials/Partner/Map.html`, new in this version, renders the
map for a list of partners or for a single one, see :ref:`feature-1790618147`.
Given a single partner with the argument :html:`partner`, it renders no map when
the partner has no coordinates or when :guilabel:`Show on map` is switched off
for it. It checks :html:`{partner.shownOnMap}`, which combines the two rules of
version 2.4, :ref:`important-map-shows-only-located-partners` and
:ref:`important-partner-map-honours-show-on-map`.

Impact
======

A template of a site package that renders the map for one partner, on a partner
page for example, renders the partial with the argument :html:`partner` instead
of guarding a map of its own:

..  code-block:: html

    <f:render partial="Partner/Map" arguments="{partner: partner, map: mapSettings, assets: mapAssets}" />

:html:`{mapAssets}` is the switch of the map script, see
:ref:`feature-1791566502`.

A template that keeps its own map and guards it with :html:`{partner.drawable}`
still draws a partner whose :guilabel:`Show on map` is switched off. Guard it
with :html:`{partner.shownOnMap}`. A project that overrides the partial has to
add the same check to its own copy.

.. index:: Frontend, Fluid, ext:academic_partners
