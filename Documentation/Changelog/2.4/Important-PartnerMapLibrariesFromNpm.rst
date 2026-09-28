..  _important-partner-map-libraries-from-npm:

==========================================================
Important: The map libraries are built from their packages
==========================================================

Description
===========

The partner map loads Leaflet 1.9.4 and the marker cluster plugin 1.5.3 as the
classic scripts :file:`Resources/Public/JavaScript/leaflet.js` and
:file:`markerCluster.js`. They were minified copies of those releases, edited to
publish the global :js:`LeafletObject` instead of :js:`L` by replacing the text.
That edit also changed the path command Leaflet writes for lines and polygons,
so those were not drawn: the area a cluster covers, which the plugin shows while
the pointer rests on a cluster, stayed invisible.

Both files are now built from the npm packages of the same releases. Their
paths do not change, and they publish what they published before:
:js:`LeafletObject` and :js:`leaflet`, :js:`LeafletObject.noConflict()`, and
:js:`Leaflet.markercluster`.

The popup of a marker is built from elements now. It looks as before, and a
partner title with characters such as :html:`<` or :html:`&` is shown as it is
written.

Impact
======

*   The area of a cluster is drawn on hover.
*   A template or script that loads the two files, or reads
    :js:`window.LeafletObject`, keeps working unchanged.
*   The stylesheets and the marker icon are unchanged.

.. index:: Frontend, JavaScript, ext:academic_partners
