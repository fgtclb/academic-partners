.. _important-partner-queries-are-ordered-deterministically:

========================================================
Important: Partner queries are ordered deterministically
========================================================

Description
===========

Every query of this extension executed without an ordering, so the order of its
result was whatever the database happened to yield. On PostgreSQL that is not
the same list twice: the partnership teaser rendered a different partner on two
renders of the same data. The queries now order explicitly:

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

Partnership lists and teasers now render in the order the records have in the
backend. An installation whose editors reordered partnership records will see
the frontend follow that order — which is the order the editor expressed, but
was never delivered before. Everything else keeps its practical order: the
backend page sorting and :sql:`uid` ascending are what every supported
database returned in practice, they are simply guaranteed now rather than
coincidental.

Affected Installations
======================

Every installation of this extension.

.. index:: Frontend, PHP-API, ext:academic_partners
