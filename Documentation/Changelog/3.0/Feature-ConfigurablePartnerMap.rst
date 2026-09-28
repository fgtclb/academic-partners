.. _feature-1790618147:

=================================================
Feature: The partner map is configurable per site
=================================================

Description
===========

Everything about the map of the :guilabel:`Partners Map` content element was
fixed in its JavaScript: the centre over Germany, the zoom, how far it zooms in,
the padding around the partners, the tile server and the attribution. A map
with a single partner zoomed in to street level, and a site outside Germany or
with a tile server of its own had to copy and rebuild the script.

*   Seven site settings of the set `fgtclb/academic-partners-map`, and
    constants of the same names for the static template:
    :typoscript:`plugin.tx_academicpartners.map.centerLatitude`,
    :typoscript:`centerLongitude`, :typoscript:`zoom`, :typoscript:`maxZoom`,
    :typoscript:`padding`, :typoscript:`tileUrl` and :typoscript:`attribution`.
    Their defaults are the values the map used so far.
*   A tab :guilabel:`Layout` on the content element with the field
    :guilabel:`Map width`. :guilabel:`Full width` adds the class
    `academic-partners-map--full-width` to the element, for the theme to style.
    The extension ships no style for it.
*   The map moved from the plugin template into the partial
    :file:`Partner/Map.html`, which takes a list of partners or a single
    partner, and the map settings. The data processor `partner-data` of the
    partner page adds the map settings as :html:`{mapSettings}`, so a page
    template can show the location of the partner of the page:

    ..  code-block:: html

        <f:render partial="Partner/Map" arguments="{partner: partner, map: mapSettings}" />

    The shipped page template does not render a map.

See :ref:`configuration-map`.

Impact
======

*   A site that configures nothing sees the map it saw before.
*   The settings reach the map as `data-academic-partners-*` attributes of the
    element :html:`<div id="map">`, listed in :ref:`configuration-map`. A
    project that overrides :file:`Templates/Partner/Map.html` renders that
    element without them, so its map keeps the values it had until the project
    renders the partial or adds the attributes. A project that ships its own
    :file:`Partner/Map.html` partial overrides the shipped one, and it is
    rendered with the arguments :html:`partners` and :html:`map`.
*   The FlexForm of the map, :file:`Configuration/FlexForms/MapSettings.xml`,
    has two sheets now. The fields of the first one kept their sheet `sDEF` and
    their names, so stored values and page TSconfig that addresses them keep
    working.
*   A project that copied the map JavaScript only to change the centre, or added
    a field of its own for a full width map, can drop it with this update.

.. index:: Frontend, FlexForm, TypoScript, NotScanned
