..  index:: ! Styling
..  _styling:

=======
Styling
=======

The plugins and the partner page of this extension render their markup with
the class system of the academic extensions and ship no styling for it. The
system, the classes of Bootstrap it keeps and how a site package styles the
markup are described once, in the chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost elements
======================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin or page
        -   Content element or page type
        -   Outermost element
    *   -   Partner list
        -   `academicpartners_list`
        -   `academic-partners-list`
    *   -   Partner map
        -   `academicpartners_map`
        -   `academic-partners-map`, with `layout-fullWidth` for the
            :guilabel:`Map width` :guilabel:`Full width`
    *   -   Partnerships list
        -   `academicpartners_partnershipslist`
        -   `academic-partnerships-list`
    *   -   Partnerships teaser
        -   `academicpartners_partnershipsteaser`
        -   `academic-partnerships-teaser`
    *   -   Partner page
        -   the page type of a partner
        -   `academic-partners-page container`

The partnerships list and teaser name the partnerships in their outermost
class, `academic-partnerships-*`, rather than the extension,
`academic-partners-*`. A rule for every plugin of the extension therefore names
both prefixes.

..  _styling-list:

The partner list
================

..  code-block:: html

    <div class="academic-partners-list">
        <!-- the header of the content element, when the plugin renders it -->
        <div class="ace-content">
            <form class="ace-form" …>
                <div class="ace-filters">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="ace-filter ace-field ace-select-wrap">
                                <label class="ace-label" for="…">Country</label>
                                <select class="ace-control ace-select" …>…</select>
                            </div>
                        </div>
                        <div class="col-12">
                            <details class="ace-more">
                                <summary class="ace-toggle">More filters</summary>
                                <div class="row">…</div>
                            </details>
                        </div>
                    </div>
                </div>
            </form>
            <ul class="ace-list ace-active-filters" aria-label="…">
                <li class="ace-list-item ace-active-filter">
                    <a class="ace-link" href="…">Europe <span class="ace-icon" aria-hidden="true"> ×</span></a>
                </li>
            </ul>
            <a class="ace-link" href="…">Reset filters</a>
            <p class="ace-count">12 partners</p>
            <div class="ace-itemlist">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <article class="ace-item">…</article>
                    </div>
                </div>
            </div>
            <nav aria-label="…">
                <ul class="f3-widget-paginator">…</ul>
            </nav>
        </div>
    </div>

*   The sorting selects come first in the filter bar, then one filter per
    category type. The filters beyond the visible ones of the content element
    are inside the :html:`<details class="ace-more">`, which is :html:`open`
    while one of them is set. The form submits itself when a select changes.
*   The active filters, the reset link and the count are only rendered when the
    content element switches them on, see :ref:`configuration-active-filters`.
*   A list without partners renders the message in a
    :html:`<span class="ace-empty">` in place of the item list.
*   The pagination is built up like the one of the job list, with
    `page-item` entries holding a `page-link`.

..  _styling-item:

A partner
---------

The partner list, the partnerships list and the partnerships teaser render a
partner the same way:

..  code-block:: html

    <article class="ace-item">
        <div class="ace-item-content">
            <header class="ace-header">
                <h2 class="ace-title"><a href="…">Partner name</a></h2>
            </header>
            <ul class="ace-list ace-attributes">
                <li class="ace-list-item ace-attribute">
                    <span class="ace-icon">…</span>
                    <b class="ace-label">Country:</b>
                    <span class="ace-value">France, Italy</span>
                </li>
            </ul>
        </div>
        <!-- the first image of the partner, preset "logo" -->
    </article>

The attributes are the categories of the partner, one `ace-attribute` per
category type.

..  _styling-partnerships:

The partnerships list and teaser
================================

Both render the partners of the current page in an `ace-itemlist`, with the
grid of the partner list. With roles, every role renders an `ace-itemlist` of
its own, whose `row` starts with the heading of the role, a
:html:`<header class="ace-header">` around an `ace-title`, before the grid
columns. A partner is built up as above.

..  _styling-map:

The partner map
===============

..  code-block:: html

    <div class="academic-partners-map layout-fullWidth">
        <!-- the header of the content element, and the filter form as in the list -->
        <div id="map" class="ace-map" data-academic-partners-…></div>
        <ul id="map-partners" class="ace-list" hidden>
            <li class="ace-list-item" data-academic-partners-map-partner …>
                <span class="ace-title">Partner name</span>
            </li>
        </ul>
    </div>

*   The map script reads the partners from the hidden list and draws them on
    the `ace-map` with the library Leaflet. The stylesheets of Leaflet and of
    its marker cluster plugin are loaded with the script, they belong to the
    library. `plugin.tx_academicpartners.assets.js` switches the script and
    these stylesheets off together, for a site that draws the map with a script
    of its own, see :ref:`configuration-javascript`.
*   The `ace-map` needs a height from the site stylesheet. Without one, the map
    has none and stays empty.
*   A map without a partner to show renders a :html:`<p class="ace-empty">`
    in place of the map.
*   `layout-fullWidth` is the only layout class there is, the
    :guilabel:`Content width` renders none. What full width means is decided by
    the site stylesheet, see :ref:`configuration-map`.

..  _styling-page:

The partner page
================

..  code-block:: html

    <div class="academic-partners-page container">
        <div class="ace-hero">
            <header class="ace-header">
                <h1 class="ace-title">Partner name</h1>
                <p class="ace-subtitle">…</p>
            </header>
            <!-- the first image of the partner, preset "detail" -->
        </div>
        <ul class="ace-list ace-attributes">…</ul>
        <p class="ace-address">
            <b class="ace-label">Address:</b>
            <span class="ace-value">…</span>
        </p>
        <!-- the content elements of the page -->
    </div>

The categories of the page are the attributes of a partner, see
:ref:`styling-item`. The page renders inside the layout of the site, see
:ref:`partner-page-layout`.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-partners-map .ace-map {
        height: 500px;
    }

    .academic-partners-map.layout-fullWidth {
        width: 100vw;
        margin-left: calc(50% - 50vw);
    }

    .academic-partners-list .ace-active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        list-style: none;
        padding-left: 0;
    }

The partner map of the development instances is styled by the partial
`_academic-partners.scss
<https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-partners.scss>`__
of the site package `academics_dev_site`, a complete example to copy from.

To change the markup itself, override the partials of the extension, the page
partials as described in :ref:`partner-page-partials`.
