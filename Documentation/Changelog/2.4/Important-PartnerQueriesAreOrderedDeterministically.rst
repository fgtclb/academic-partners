..  _important-partner-queries-are-ordered-deterministically:

========================================================
Important: Partner queries are ordered deterministically
========================================================

Description
===========

Most queries of this extension executed without an ordering, so the order of
their result was whatever the database happened to yield. On PostgreSQL that is
not reliably the same list twice, so a partnership teaser could render a
different partner on two renders of the same data. The queries now order
explicitly:

*   :php:`PartnershipRepository::findByPid()`, the source of every partnership
    list and teaser, orders by the manual backend :sql:`sorting` (TCA ctrl
    :php:`sortby`), with :sql:`uid` settling ties.
*   :php:`PartnerRepository::findByDemand()`, the source of the partner list
    and the partner map, appends :sql:`uid` as a tiebreaker to the ordering the
    plugin demands. Records equal in that ordering, two partners with the same
    title for example, keep a stable relative order.
*   :php:`PartnerRepository::findNextForGeolocation()` processes the geocoding
    queue oldest record first (:sql:`uid` ascending) instead of letting the
    database pick.
*   :php:`PartnerRepository::findAll()` and :php:`findGeoLocated()` order by
    the backend :sql:`sorting` of the page records, with :sql:`uid` settling
    ties. Partner records are pages, so among siblings this is the order the
    editor arranged in the page tree.

Impact
======

Partnership lists and teasers now render in the order of the partnerships'
:sql:`sorting`. Partnerships are inline records of their partner page: a new
one is added at the bottom, and saving the page numbers them in the order of
its form, so a list maintained on the partner page keeps its oldest-first
order unless an editor rearranged it. They are inline records of their role as
well, and that relation wrote the same :sql:`sorting` column until the role
gained a sort column of its own, see
:ref:`important-partner-role-sorts-its-partnerships`.

The partner list and the partner map keep the ordering configured in the
plugin. Only partners that are equal in it are ordered by :sql:`uid` now.

The geocoding queue and the tiebreakers use :sql:`uid` ascending, the order
SQLite, MySQL and MariaDB return in practice. On PostgreSQL the previous order
was not reliable to begin with.

Nothing the extension renders reads :php:`findAll()` or :php:`findGeoLocated()`
in an order that shows: the partner select of a partnership record sorts its
entries by label, see :ref:`important-ace-669-academic-partners`. Code of a
project that calls them gets the page tree order. The backend typically places
a page created below a parent page above the existing ones, so partner pages
nobody rearranged come out newest first, where the database returned them
oldest first.

Affected Installations
======================

Every installation of this extension. Nothing has to be done on update.

..  index:: Frontend, PHP-API, ext:academic_partners
