..  _breaking-ace-392-academic-partners-map-module:

====================================
Breaking: The map script is a module
====================================

Description
===========

Version 2.4 built the stylesheet and the script of the partner map from sources
and moved them into a :file:`frontend/` subdirectory, see
:ref:`breaking-map-assets-are-built`. The script was still a classic script. It
is an **ES module** now, registered in :file:`Configuration/JavaScriptModules.php`
and addressed by the bare specifier
:code:`@fgtclb/academic-partners/frontend/map.js`. The stylesheet stays a
stylesheet at :file:`Css/frontend/map.css`.

The map module imports Leaflet and its marker cluster plugin as modules as well,
see :ref:`important-1790623331`.

Impact
======

An installation that uses the shipped templates needs to do nothing.

An installation that loads :file:`JavaScript/frontend/map.js` with
:html:`<f:asset.script>` gets a classic script tag, which does not execute an ES
module, and the map stays empty.

Affected Installations
======================

Installations that override :file:`Templates/Partner/Map.html` or the partial
:file:`Partials/Partner/Map.html`, or reference :file:`map.js` from their own
site package.

Migration
=========

In an overridden template or partial, replace the script reference:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />

..  index:: Frontend, JavaScript, Fluid, ext:academic_partners
