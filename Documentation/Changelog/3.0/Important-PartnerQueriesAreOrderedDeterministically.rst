.. _important-partner-queries-are-ordered-deterministically:

========================================================
Important: Partner queries are ordered deterministically
========================================================

Description
===========

Most queries of this extension executed without an ordering, so the order of
their result was whatever the database happened to yield. On PostgreSQL that is
not the same list twice: the partnership teaser rendered a different partner on
two renders of the same data. The queries now order explicitly:

*   :php:`PartnershipRepository::findByPid()` — the source of every partnership
    list and teaser — orders by the manual backend :sql:`sorting` (TCA ctrl
    :php:`sortby`), with :sql:`uid` settling ties.
*   :php:`PartnerRepository::findAll()` and :php:`findGeoLocated()` order by
    the backend :sql:`sorting` of the page records, with :sql:`uid` settling
    ties — partner records are pages, so among siblings this is the order the
    editor arranged in the page tree; partners spread over several parent
    pages interleave deterministically by that same value.
*   :php:`PartnerRepository::findNextForGeolocation()` processes the geocoding
    queue oldest record first (:sql:`uid` ascending) instead of letting the
    database pick.
*   :php:`PartnerRepository::findByDemand()` appends :sql:`uid` as a tiebreaker
    to the ordering the plugin demands, so records equal in that ordering — two
    partners with the same title, for example — keep a stable relative order.

Impact
======

Partnership lists and teasers now render in the order of the partnerships'
:sql:`sorting`. Partnerships are inline records of their partner page: a new
one is added at the bottom, and saving the page numbers them in the order of
its form, so a list maintained on the partner page keeps its oldest-first
order unless an editor rearranged it. They are inline records of their role as
well, and that relation wrote the same :sql:`sorting` column until the role
gained a sort column of its own - see *Important: A partner role sorts its
partnerships on its own* in this changelog.

Partners on the map follow the page tree among siblings. The backend typically
places a page created below a parent page above the existing ones, so partner
pages nobody rearranged are sorted newest first, while the database returned
them oldest first (:sql:`uid` order) — that order can appear reversed. Arrange
the pages in the page tree where the new order is not the intended one.

The geocoding queue and the tiebreakers use :sql:`uid` ascending, the order
SQLite, MySQL and MariaDB return in practice; on PostgreSQL the previous order
was not reliable to begin with.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_partners
