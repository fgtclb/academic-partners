.. _deprecation-1790623332:

===================================================
Deprecation: The classic files of the map libraries
===================================================

Description
===========

The partner map no longer loads these files, see :ref:`important-1790623331`:

*   :file:`Resources/Public/JavaScript/leaflet.js`
*   :file:`Resources/Public/JavaScript/markerCluster.js`
*   :file:`Resources/Public/Css/leaflet.css`
*   :file:`Resources/Public/Css/markerCluster.css`
*   :file:`Resources/Public/Css/images/`

They stay where they are, unchanged, for a project that loads them from its own
template or script. They are removed in version 4.0.

Impact
======

Nothing is logged. A project that references one of the files keeps working
until 4.0.

Migration
=========

Import `leaflet` and `leaflet.markercluster` in a module, and register the
stylesheets from
:file:`EXT:academic_partners/Resources/Public/JavaScript/vendor/<library>/<version>/`,
as :file:`Partials/Partner/Map.html` does. A script that needs the global of the
classic files can publish a copy of the module, which unlike the module itself
can take the members a classic plugin adds:
:js:`import * as leaflet from 'leaflet'; window.LeafletObject = { ...leaflet };`.

The marker images of :file:`Resources/Public/Css/images/` are the map's own
marker icon, and the map now loads them from
:file:`Resources/Public/Images/Map/`.

.. index:: Frontend, JavaScript, NotScanned
