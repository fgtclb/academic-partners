..  _breaking-map-assets-are-built:

============================================
Breaking: The map assets are built and moved
============================================

Description
===========

The stylesheet and the script of the partner map are now compiled from sources
in the repository, and both moved into a :file:`frontend/` subdirectory:

..  code-block:: text

    EXT:academic_partners/Resources/Public/Css/map.css
    ->  EXT:academic_partners/Resources/Public/Css/frontend/map.css

    EXT:academic_partners/Resources/Public/JavaScript/map.js
    ->  EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js

The vendored Leaflet library, its marker cluster plugin and their stylesheets
are **unchanged**. They are third party files without sources here, they keep
their paths, and the map script still reads the :js:`LeafletObject` global they
define. Both are still loaded as classic scripts.

Impact
======

An installation that uses the extension as shipped needs to do nothing: the
files are loaded by the extension itself, from the new location.

An installation that referenced the old path keeps pointing at a file that no
longer exists.

Affected installations
======================

Installations that override :file:`Templates/Partner/Map.html` or reference
:file:`map.css` or :file:`map.js` from their own site package.

Migration
=========

In an overridden template, add the :file:`frontend/` segment to the two
references:

..  code-block:: html

    <f:asset.css identifier="partnerC2" href="EXT:academic_partners/Resources/Public/Css/frontend/map.css" />
    <f:asset.script identifier="partnerS2" src="EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js" />

The three Leaflet lines around them stay exactly as they are.
