..  _breaking-map-assets-are-built:

=========================================================
Breaking: The map assets are built and loaded as a module
=========================================================

Description
===========

The stylesheet and the script of the partner map are now compiled from sources
in the repository. Both moved into a :file:`frontend/` subdirectory, and the
script became an **ES module**:

..  code-block:: text

    EXT:academic_partners/Resources/Public/Css/map.css
    ->  EXT:academic_partners/Resources/Public/Css/frontend/map.css

    EXT:academic_partners/Resources/Public/JavaScript/map.js
    ->  EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js

The vendored Leaflet library, its marker cluster plugin and their stylesheets
are **unchanged**. They are third party files without sources here, they keep
their paths, and they are still loaded as classic scripts — the map module
reads the :js:`LeafletObject` global they define.

Impact
======

An installation that uses the shipped :file:`Map.html` template needs to do
nothing.

An installation that references either path keeps pointing at a file that no
longer exists.

Affected installations
======================

Installations that override :file:`Templates/Partner/Map.html` or reference
:file:`map.css` or :file:`map.js` from their own site package.

Migration
=========

In an overridden template, replace the two references:

..  code-block:: html

    <f:asset.css identifier="partnerC2" href="EXT:academic_partners/Resources/Public/Css/frontend/map.css" />
    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />

The three Leaflet lines around them stay exactly as they are.
