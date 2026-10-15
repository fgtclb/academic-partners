..  _integrator-partner-map:

===========
Partner Map
===========

..  image:: /Images/Icons/map.svg
    :width: 48px
    :alt: Icon of the Partner Map content element

:CType:
    ``academicpartners_map``

:Plugin:
    `AcademicPartners`, plugin `Map`, action
    :php:`PartnerController::mapAction()`

:Set:
    `fgtclb/academic-partners-map`, which the aggregate set
    `fgtclb/academic-partners` includes, see :ref:`configuration-components`

The :guilabel:`Partner Map` draws the partners of a site on a map, with the tiles
of OpenStreetMap by default. Every partner is a marker, nearby markers are
merged into clusters while the map is zoomed out, and a marker opens a popup
with the name of the partner, linked to its page. The map fits itself around
the partners it draws. Above the map the same form as in
the :ref:`Partner List <integrator-partner-list>` lets the visitor filter the
partners by category.

..  figure:: /Images/Screenshots/partner-map-default.jpg
    :alt: An OpenStreetMap map with the markers of four partners.
    :class: with-border with-shadow

    The Partner Map with a marker per partner it can draw.

It is the element for a page that shows where the partners of a university are,
all of them or those of one programme or kind of cooperation. It finds its
partners exactly as the list does, so a page can show the list and the map of
the same selection.

The element is not cached, and a selection of the visitor is part of the URL,
as in the list. It loads the map script and the stylesheets of the map
libraries on the page that carries it, see :ref:`configuration-javascript`.

..  _integrator-partner-map-partners:

Which partners the map draws
============================

The map draws the partners the filter matches that have coordinates and whose
page has :guilabel:`Show on map` switched on, the switch next to the coordinates
of the partner page. The switch is on for a new partner. It is about the map
only: the partner list lists a partner hidden from the map.

The map reads the switch of the partner page in the language it is rendered in.
The switch of a translation follows its default record, as the coordinates do,
so switching a partner off in the default language takes it off the map in every
language. An editor who detaches the switch of a translation sets it for that
language on its own.

A map with no partner to draw renders a message instead of the map: "No
partners found." while a category filter is active, and "No partner with a
location to show on the map." otherwise.

Related chapters:

*   :ref:`configuration-map` describes the site settings of the map: its
    centre, its zoom, the tile server and the attribution, and the map on
    other pages.
*   :ref:`configuration-map-styling` is required reading: the map element
    needs a height from the stylesheet of the site, or it shows no map at all.
*   :ref:`styling-map` describes the markup.
*   :ref:`configuration-list-filter` and :ref:`configuration-active-filters`
    apply to the form of the map as well.

..  _integrator-partner-map-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds the options of
the map, on two tabs of their own, :guilabel:`Filter` and :guilabel:`Layout`,
and below them the field :guilabel:`Startingpoint`. The map has no
:guilabel:`Pagination` tab: it always draws every partner the filter matches.

..  confval:: Startingpoint
    :name: partner-map-pages
    :type: page
    :Default: empty

    The pages or storage folders the partner pages sit in, as for the list, see
    :confval:`Startingpoint <partner-list-pages>`. Only direct subpages of the
    chosen pages are found, and an empty value draws every partner page of the
    installation, those of every other site included.

Filter
------

The tab carries the same fields as the one of the list, and they work the same
way. What is different on a map is noted below.

..  confval:: Hide category filtering
    :name: partner-map-hide-filter
    :type: checkbox
    :Default: off

    On, the form shows no category selects, and neither active filter tags nor a
    reset link. The map always draws the partners of :confval:`Default
    categories <partner-map-categories>`, also when the visitor changes the
    sorting, and categories in the URL of a link only narrow it further. See
    :confval:`Hide category filtering <partner-list-hide-filter>`.

..  confval:: Hide sorting
    :name: partner-map-hide-sorting
    :type: checkbox
    :Default: off

    On, the form shows no sorting selects. A map has no visible order, the
    sorting only orders the hidden list the map script reads its partners from.
    Switch it on for a map unless a script of the site renders that list.

..  confval:: Default sorting
    :name: partner-map-sorting
    :type: select
    :Default: Title A-Z

    The order of the partners in the hidden list of the map, with the items of
    :confval:`Default sorting <partner-list-sorting>`. It does not change what
    the map draws. A value that is not a field and a direction is ignored, as in
    the list.

..  confval:: Default categories
    :name: partner-map-categories
    :type: records
    :Default: empty

    Categories the map is filtered by until the visitor selects something in the
    form. A partner is drawn only when its page carries every chosen category,
    and subcategories are not added. The description below the field in the
    backend says otherwise, see :confval:`Default categories
    <partner-list-categories>`. With the filter hidden, the map keeps them
    whatever the visitor submits or a link carries.

..  confval:: Show hidden records
    :name: partner-map-show-hidden-records
    :type: checkbox
    :Default: off

    On, the map draws hidden partner pages as well, for every visitor. The link
    of their popup leads to a page the visitor cannot open. Start and stop
    times, access restrictions and deleted pages still apply.

Layout
------

..  confval:: Map width
    :name: partner-map-map-layout
    :type: select
    :Default: Content width

    How wide the map renders.

    ..  list-table::
        :header-rows: 1

        *   -   Item
            -   Renders
        *   -   :guilabel:`Content width`, the default
            -   :html:`<div class="academic-partners-map">`
        *   -   :guilabel:`Full width`
            -   :html:`<div class="academic-partners-map layout-fullWidth">`

    The extension ships no style for the class. What full width means depends
    on the page layout of the site, so the theme of the site styles it. A
    content element saved before the field existed renders at content width.

..  figure:: /Images/Screenshots/partner-map-full-width.jpg
    :alt: A map that spans the full width of the page, wider than the text of the page above it.
    :class: with-border with-shadow

    The Partner Map with the map width Full width, as the stylesheet of the
    development instance styles it.
