.. _important-partner-queries-are-ordered-deterministically:

========================================================
Important: Partner queries are ordered deterministically
========================================================

Description
===========

Most queries of this extension executed without an ordering, so the order of
their result was whatever the database happened to yield. On PostgreSQL that is
not the same list twice: on the 3.x line the partnership teaser rendered a
different partner on two renders of the same data, and the queries here are the
same. They now order explicitly:

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

Partnership lists and teasers now render in the order the partnerships have on
their partner page. Partnerships are inline records of that page: a new one is
added at the bottom, and saving the page numbers them in the order of the form.
A list nobody rearranged therefore keeps its previous order, oldest first. A
list an editor rearranged now follows that arrangement, which was never
delivered before.

Partners on the map follow the page tree. The backend typically places a page
created in a folder above the existing ones, so partner pages nobody rearranged
are sorted newest first, while the database returned them oldest first
(:sql:`uid` order) — that order can appear reversed. Arrange the pages in the
page tree where the new order is not the intended one.

The geocoding queue and the tiebreakers use :sql:`uid` ascending, the order
every supported database returned in practice, so they change nothing visible.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_partners
