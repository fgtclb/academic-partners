.. _feature-1791043402:

=====================================================
Feature: Route enhancers for the partner list and map
=====================================================

Description
===========

The extension ships route enhancers for the :guilabel:`Partners List` and the
:guilabel:`Partners Map` in :file:`Configuration/Routes/List.yaml`. A site
that imports the file gets readable list URLs in the language of the site:

..  code-block:: text

    /partners/filter/europe-1,university-3/title/desc/page-2
    /de/partner/filter/europa-1,universitaet-3/titel/absteigend/seite-2

Every combination of filter, sorting and page, and of filter and sorting on
the map, is a route of its own, so a filter alone or a sorting alone resolves
as well. A single enhancer route with defaults generates a broken path for
one of them and query arguments for the other. The filter segment uses the
:yaml:`CategoryFilterMapper` aspect of :guilabel:`category_types`.

..  code-block:: yaml
    :caption: config/sites/my_site/config.yaml

    imports:
      - resource: 'EXT:academic_partners/Configuration/Routes/List.yaml'

    routeEnhancers:
      AcademicPartnersList:
        limitToPages: [12]
      AcademicPartnersMap:
        limitToPages: [13]

See :ref:`configuration-route-enhancers`.

Impact
======

*   Nothing changes for a site that does not import the file.
*   A site that imports it removes its own enhancer for the two plugins, if it
    has one. The redirect of the filter form, the pagination and the active
    filter tags lead to the paths.
*   Each of the six sortings is a page cache entry of its own per page and
    language, and a path without a sorting one more. Filters and pages share
    one entry, as with query arguments.

.. index:: Frontend, NotScanned
