.. _important-1790623331:

=========================================================
Important: The partner map loads its libraries as modules
=========================================================

Description
===========

Version 2.4 built Leaflet 1.9.4 and the marker cluster plugin 1.5.3 from their
npm packages, still as the two classic scripts in
:file:`Resources/Public/JavaScript/`, see
:ref:`important-partner-map-libraries-from-npm`. They are built as modules now,
at the same versions, into
:file:`Resources/Public/JavaScript/vendor/<library>/<version>/` with their
licences and stylesheets. The import map of the extension publishes them as
`leaflet` and `leaflet.markercluster`, and the map module imports them.

Impact
======

*   A page with the map loads the modules through the import map and the
    stylesheets from the version directories. It loads none of the classic files
    any more, see :ref:`deprecation-1790623332`.
*   The map sets no global variable. A project script that used
    :js:`window.LeafletObject`, or the other globals of the classic files, has
    to import `leaflet` instead, or load the deprecated classic files itself. A
    template that loads the classic scripts next to the shipped partial loads
    Leaflet twice, and the two do not share anything.
*   The stylesheet of the marker cluster plugin is loaded in full now. The copy
    the extension shipped held only its default look, so the animation of
    clusters that open and close was missing.
*   A project that overrides :file:`Partials/Partner/Map.html` still registers
    whatever its copy registers. It has to take over the stylesheets, the
    marker icon attribute and the module, and drop the classic scripts, to get
    the modules. The asset identifiers :html:`partnerC0`, :html:`partnerC1`,
    :html:`partnerS0` and :html:`partnerS1` are gone, and so is
    :html:`partnerC2` together with the stylesheet of the map, see
    :ref:`breaking-partner-map-ships-no-stylesheet`.
*   The marker icon is the one the map showed before. Its images are copied to
    :file:`Resources/Public/Images/Map/`, and the partial names the icon in the
    attribute `data-academic-partners-marker-icon` of the map element. A
    template without it shows the icon of Leaflet.
*   The specifiers `leaflet` and `leaflet.markercluster` are global to the page.
    When another extension or the theme maps `leaflet` as well, the import map
    holds only one of them, and the map and the other code share whichever
    Leaflet that is.

.. index:: Frontend, JavaScript, NotScanned
