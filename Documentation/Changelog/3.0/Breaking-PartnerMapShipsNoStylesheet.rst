..  _breaking-partner-map-ships-no-stylesheet:

=============================================
Breaking: The partner map ships no stylesheet
=============================================

Description
===========

The partner map no longer brings a stylesheet of its own. The file
:file:`EXT:academic_partners/Resources/Public/Css/frontend/map.css` and its
source are removed, and the partial :file:`Partner/Map.html` no longer
registers it. The asset identifier :html:`partnerC2` is gone with it.

The stylesheets of Leaflet and of its marker cluster plugin are not affected.
The map does not work without them, so the partial keeps registering them
together with the map module, from
:file:`Resources/Public/JavaScript/vendor/<library>/<version>/`.

The removed file did three things, and they are the site's now:

*   it gave the map element, :html:`<div id="map" class="ace-map">`, a height
    of 500 pixels,
*   it put the panes and the controls of the map behind the chrome of the site,
    with :css:`z-index: 0`, and
*   it kept the image rules of a theme off the tiles and the markers, with
    :css:`width: auto !important`.

The rules are kept as an example in the mono repository the extension is
developed in: the stylesheet of its development instances,
`_academic-partners.scss of EXT:academics_dev_site
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-partners.scss>`__,
which is never shipped with an extension.

Impact
======

**The map is not visible without a height.** Leaflet draws into the element the
partial renders, and an element without a height of its own has none. A site
whose stylesheet does not size the map shows no map at all.

A theme rule such as :css:`img { max-width: 100% }` or
:css:`img { width: 100% }` reaches the tiles and the markers again, and the map
shows them distorted or with gaps. The controls of the map may cover a sticky
header of the site.

Affected Installations
======================

Every installation that renders the :guilabel:`Partners Map` content element,
or the partial :file:`Partner/Map.html` from a page template, and has no
stylesheet of its own for the map. Installations whose template override
registers :file:`Css/frontend/map.css` by path.

Migration
=========

#.  Give the map element a height in the stylesheet of the site package, and
    take over the two other rules where the theme needs them:

    ..  code-block:: css

        #map {
            height: 500px;
        }

        .leaflet-container .leaflet-top,
        .leaflet-container .leaflet-bottom,
        .leaflet-container .leaflet-pane {
            z-index: 0;
        }

        .leaflet-container .leaflet-marker-pane img,
        .leaflet-container .leaflet-shadow-pane img,
        .leaflet-container .leaflet-tile-pane img,
        .leaflet-container img.leaflet-image-layer,
        .leaflet-container .leaflet-tile {
            width: auto !important;
        }

    The stylesheets of the libraries are registered by the partial and come
    after the stylesheets of the page, so a rule that overrides one of their
    rules needs a more specific selector than theirs, as the container in the
    rules above. The removed file was registered after them and did not.
#.  An overridden :file:`Partner/Map.html` that registers
    :file:`Css/frontend/map.css` drops that line, the file does not exist any
    more. The three lines that register the stylesheets of the libraries stay.

.. index:: Frontend, Fluid, ext:academic_partners
