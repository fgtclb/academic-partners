..  _breaking-partners-speaking-frontend-classes:

==================================================
Breaking: Partner templates carry speaking classes
==================================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, form controls, spacing and display
utilities - next to block and element classes of the extension. They carry
speaking classes now, named after what an element is and shared by all academic
extensions: `ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`,
`ace-link` and so on. The outermost element of a plugin keeps the class of the
plugin. The classes of Bootstrap that a site builds on stay on purpose where
the templates render them: `container` on the outermost element of a page of
the page type, the grid classes `row` and `col-*`, the button classes `btn` and
`btn-*` and `visually-hidden`. No other theme class is rendered any more, and
the extension ships no styles for the new classes.

The header partials take the class of the heading as the argument `class`,
in place of `positionClass`. An override that renders one of them with
`positionClass` renders the heading without that class.

The map script finds the partners of the map by the attribute
:html:`data-academic-partners-map-partner` now, no longer by the class
`map-partner`.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Item list
        -   `row academic-partners-itemlist`
        -   `ace-itemlist` around the `row`
    *   -   Item
        -   `card flex-column-reverse academic-partners-item mt-4` with `card-content` and `card-body`
        -   `<article class="ace-item">` with `ace-item-content`
    *   -   Attributes of an item
        -   `list-group list-group-flush` with `list-group-item` entries
        -   `ace-list ace-attributes` with `ace-list-item ace-attribute` entries, `ace-icon`, `ace-label` and `ace-value` inside
    *   -   No result
        -   a plain `<span>`
        -   `ace-empty`
    *   -   Filter form
        -   `academic-partners-filtersorting`
        -   `ace-form`, the fields in an `ace-filters`
    *   -   Filter cell
        -   the grid column only
        -   `ace-filter ace-field ace-select-wrap` inside the column
    *   -   Label and select
        -   `form-label`, `form-select`
        -   `ace-label`, `ace-control ace-select`
    *   -   More filters
        -   `<details class="col-12 academic-partners-more-filters">`
        -   `<details class="ace-more">` inside a `col-12`, with `<summary class="ace-toggle">`
    *   -   Result count
        -   `academic-partners-result-count`
        -   `ace-count`
    *   -   Active filters
        -   `academic-partners-active-filters` with `__tags`, `__tag`, `__remove`
        -   `<ul class="ace-list ace-active-filters">` with `ace-list-item ace-active-filter` entries, the remove mark `ace-icon`
    *   -   Reset link
        -   `academic-partners-active-filters__reset`
        -   `ace-link`
    *   -   Page
        -   `academic-partners-detail container`
        -   `academic-partners-page container`
    *   -   Header and media
        -   `d-flex flex-column-reverse`
        -   `ace-hero`
    *   -   Page header
        -   `academic-partners-detail__header`
        -   `<header class="ace-header">` with `ace-title` on the heading
    *   -   Subtitle
        -   `academic-partners-detail__subtitle`
        -   `ace-subtitle`
    *   -   Partnerships
        -   `academic-partnerships-list-item` and `academic-partnerships-teaser-item`
        -   `<article class="ace-item">` in an `ace-itemlist`
    *   -   Address of the page
        -   a plain paragraph
        -   `ace-address` with `ace-label` and `ace-value`
    *   -   Pagination
        -   `academic-partners-list__pagination` around `pagination`
        -   `f3-widget-paginator`, the entries keep `page-item` and `page-link`
    *   -   Map element
        -   `academic-partners-map`, `academic-partners-map--full-width` at full width
        -   `academic-partners-map`, `layout-fullWidth` at full width
    *   -   Partners of the map
        -   `<ul id="map-partners" class="d-none">` with `map-partner` entries
        -   `<ul id="map-partners" class="ace-list" hidden>` with `ace-list-item` entries carrying `data-academic-partners-map-partner`, the title in an `ace-title`
    *   -   Map without partners
        -   `academic-partners-map-empty`
        -   `ace-empty`

Impact
======

*   A site stylesheet that styles the partner list, the partnerships, the
    filters, the partner page or the map through the classes of the column
    *Before* no longer matches.
*   An override of :file:`Partner/Map.html` that renders the partners with the
    class `map-partner` and without the attribute
    `data-academic-partners-map-partner` shows a map without markers.
*   The list of partners next to the map is hidden by the attribute
    :html:`hidden` in place of the class `d-none` of a Bootstrap theme.

Affected Installations
======================

Every installation that renders the partner list, the partner map, the
partnership list or teaser, or a page of the partner page type.

Migration
=========

#.  Move the selectors of the site stylesheet from the classes in the
    column *Before* to those in the column *After*. A site that styled the
    plugins through the classes of its Bootstrap theme alone gives the new
    classes the look it wants, for example with :css:`@extend` in SCSS or
    with rules of its own.
#.  A template override is compared with the shipped template of 3.0 and
    adopts its markup. An override keeps working as it is, but renders its
    own classes, and nothing in the extension relies on them.
#.  An override of :file:`Partner/Map.html` sets the attribute
    `data-academic-partners-map-partner` on every partner.
#.  Replace `positionClass` with `class` where an override renders
    :file:`Partner/Header.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, JavaScript, ext:academic_partners
