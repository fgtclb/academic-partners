.. _feature-ace-669-academic-partners:

==============================================
Feature: The partner select is sorted by label
==============================================

Description
===========

The partner select of a partnership record now offers the partners ordered
by their title, ascending, using the collation of the language the backend
is displayed in.

Until now neither the select nor the query behind it declared an order, so
an editor got whatever order the database returned — not reliably the same
order twice on PostgreSQL. In an installation with more than a handful of
partners the entry to pick was found by scanning rather than by reading.

The order is declared on the field itself, so TYPO3 applies it with the
collator of the backend language: a title starting with a diacritic is
ordered by its base letter, and ``Öresund Academy`` is offered between
``Oberlin Institute`` and ``Potsdam College`` rather than after
``Zeta University``.

The empty placeholder entry stays at the top of the list.

Impact
======

Editors of partnership records see the same partners in a different, and
now dependable, order. Nothing changes about which partners are offered,
about what a save stores, or about how any list renders in the frontend. No
migration and no manual step is required.

There was no reliable previous order to rely on, so nothing that was
dependable before becomes undependable now.

Affected Installations
======================

All installations using the `EXT:academic_partners` extension starting with
version 2.4. No action is required for existing installations.

.. index:: Backend, ext:academic_partners
