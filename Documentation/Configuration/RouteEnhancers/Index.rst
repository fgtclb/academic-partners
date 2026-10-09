..  index:: Configuration; Route enhancers
..  _configuration-route-enhancers:

===============
Route enhancers
===============

This extension ships route enhancers for the :guilabel:`Partner List` and the
:guilabel:`Partner Map` in :file:`Configuration/Routes/List.yaml`. They turn
the category filter, the sorting and the page of a list into path segments in
the language of the site:

..  code-block:: text

    /partners/filter/europe-1,university-3/title/desc/page-2
    /de/partner/filter/europa-1,universitaet-3/titel/absteigend/seite-2

TYPO3 does not read the file on its own. A site imports it, and nothing
changes for a site that does not.

Importing it into a site configuration
======================================

Add the file to the :yaml:`imports` of the site that shows the plugins, and
limit each enhancer to the pages that carry its plugin:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_partners/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicPartnersList:
        limitToPages: [12]
      AcademicPartnersMap:
        limitToPages: [13]

TYPO3 offers every enhancer of a site to every page unless it carries
:yaml:`limitToPages`, and the first route whose path matches wins. The uids
are those of the pages in the **default language**, one entry covers every
translation of a page.

A site that wrote its own enhancer for one of the two plugins removes it when it
imports this file. Two enhancers for one plugin compete for the same URLs.

What the URLs look like
=======================

A list URL carries up to three arguments, in this order:

..  list-table::
    :header-rows: 1

    *   -   Argument
        -   English
        -   German
    *   -   Category filter
        -   :file:`/filter/europe-1,university-3`
        -   :file:`/filter/europa-1,universitaet-3`
    *   -   Sorting
        -   :file:`/title/desc`
        -   :file:`/titel/absteigend`
    *   -   Page, list only
        -   :file:`/page-2`
        -   :file:`/seite-2`

Each combination of them is a route of its own, so a filter alone, a sorting
alone or a page alone resolves as well as all three together. The map is not
paginated and has filter and sorting only.

*   **The filter** is mapped by the :yaml:`CategoryFilterMapper` aspect of
    :guilabel:`category_types`, with the group :yaml:`partners`: the title of
    each category in the language of the page, followed by its uid. See
    `its documentation
    <https://docs.typo3.org/p/fgtclb/category-types/main/en-us/Developers/Index.html>`__.
*   **The sorting** is two segments, field and direction:

    ..  list-table::
        :header-rows: 1

        *   -   Sorting
            -   English
            -   German
        *   -   Title
            -   :file:`title`
            -   :file:`titel`
        *   -   Last updated
            -   :file:`last-updated`
            -   :file:`zuletzt-aktualisiert`
        *   -   Sorting of the page tree
            -   :file:`sorting`
            -   :file:`sortierung`
        *   -   Ascending, descending
            -   :file:`asc`, :file:`desc`
            -   :file:`aufsteigend`, :file:`absteigend`

    A sorting value of the other language is a 404, not a second address of
    the same list. The filter segment is read by the uid, so it resolves
    under the title of any language.

The enhancers declare no defaults. A link with the default sorting keeps it in
the path, because the page without any argument is where the content element's
preselected categories and sorting apply. A visitor who removed a preselected
category must not get it back.

The links the list renders itself use the routes: the redirect after the filter
form, the pagination and the tags of :ref:`configuration-active-filters`.

Another language
================

The file maps English and German. A site with a further language adds
:yaml:`localeMap` items to the aspects in its own site configuration. The
import appends list items, so the site's own items come after the shipped
ones:

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    routeEnhancers:
      AcademicPartnersList:
        limitToPages: [12]
        aspects:
          page_key:
            localeMap:
              - locale: 'fr.*'
                value: 'page'
          sorting_field:
            localeMap:
              - locale: 'fr.*'
                map:
                  titre: 'title'
                  mise-a-jour: 'lastUpdated'
                  tri: 'sorting'
          sorting_direction:
            localeMap:
              - locale: 'fr.*'
                map:
                  croissant: 'asc'
                  decroissant: 'desc'

The same goes for the map, under :yaml:`AcademicPartnersMap`.

A language without :yaml:`localeMap` items of its own uses the English keys
and values. A value missing from the map of a language keeps its query
argument in that language.

The page cache
==============

The sorting is a static route argument, so each of the six sortings is a page
cache entry of its own, per page and language. A path without a sorting is one
more entry, and the page without arguments another, eight in all. The filter
and the page are dynamic arguments and add no entry: the demand of the lists
is excluded from the cache hash, so every filter and every page of a list
shares one entry.
