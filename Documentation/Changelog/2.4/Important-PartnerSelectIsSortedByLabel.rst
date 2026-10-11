..  _important-ace-669-academic-partners:

================================================
Important: The partner select is sorted by label
================================================

Description
===========

The :guilabel:`Partner` select of a partnership record now offers the partners
ordered by their title, ascending, using the collation of the language the
backend is displayed in.

Until now neither the select nor the query behind it declared an order, so an
editor got whatever order the database returned, and on PostgreSQL not
reliably the same order twice. In an installation with more than a handful of
partners the entry to pick was found by scanning rather than by reading.

The order is declared on the field itself with the TCA option
:php:`sortItems`, so TYPO3 applies it with the collator of the backend
language, on TYPO3 v12 and v13 alike. A title starting with a diacritic is
ordered by its base letter: ``Öresund Academy`` is offered between
``Oberlin Institute`` and ``Potsdam College`` rather than after
``Zeta University``.

The empty placeholder entry stays at the top of the list.

Impact
======

Editors of partnership records see the same partners in a different, and now
dependable, order. Nothing changes about which partners are offered, about what
a save stores, or about how any list renders in the frontend.

There was no reliable previous order, so nothing that could be relied on
before changes now.

Affected Installations
======================

All installations of this extension. No action is required.

..  index:: Backend, TCA, ext:academic_partners
