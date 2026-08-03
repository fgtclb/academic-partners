.. _important-1785882500:

=======================================================
Important: The `link` column of `pages` is nullable now
=======================================================

Description
===========

The extension adds a :sql:`link` column to the shared :sql:`pages` table.
It was declared as:

..  code-block:: sql

    link text NOT NULL DEFAULT '',

MySQL cannot store a default value on a :sql:`TEXT` column. TYPO3 works
around that from v13 on, by expressing the default in the
:sql:`DEFAULT ('')` syntax MySQL 8.0.13 introduced — so the declaration
is harmless on every core version this release supports. On TYPO3 v12 it
was not: the column ended up :sql:`NOT NULL` with no default at all, and
creating a page in the backend failed with
:sql:`Field 'link' doesn't have a default value`.

The column keeps its type and loses the default instead:

..  code-block:: sql

    link text DEFAULT NULL,

The change is carried here as well so that both maintained branches
declare the column identically.

Impact
======

Records written without naming the column now store :sql:`NULL` where
they previously stored an empty string. Existing rows are not changed, so
the column can hold both. Code that reads it should compare with
:php:`empty()` rather than with :php:`''`.

Affected Installations
======================

All installations of this extension. The database schema has to be
updated, either in the maintenance area of the install tool or with
:bash:`typo3 extension:setup`. The change only relaxes the column, so no
data is converted and nothing can be lost.

.. index:: Database, ext:academic_partners
