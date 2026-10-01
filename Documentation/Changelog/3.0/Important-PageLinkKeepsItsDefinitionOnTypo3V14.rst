..  _important-1790839519:

====================================================================
Important: The link of a link page keeps its definition on TYPO3 v14
====================================================================

Description
===========

TYPO3 14.0 added the field :sql:`link` to :sql:`pages`, the target of the page
type :guilabel:`Link`
(`Feature: #17406 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.0/Feature-17406-EnhancePageTypeLinkToFullySupportTypolinks.html>`__).
TYPO3 requires it, offers to keep it in step between the languages of a
page, and offers only the link options `params` and `target`.

:guilabel:`EXT:academic_partners` added a column of the same name to
:sql:`pages`. A column added under a name TYPO3 already uses replaces its
definition on every page type, so on TYPO3 v14 the target of a link page was
no longer required, became a field an editor group needs in
:guilabel:`Allowed fields`, and lost both the language synchronisation and the
restriction of the link options.

The extension now adds its column only where TYPO3 has none, which is TYPO3
v13. Its declaration in :file:`ext_tables.sql` stays, so the database column
keeps its type on both versions.

Impact
======

On TYPO3 v14 the target of a link page is required again, every editor group
that may edit pages may edit it, and it offers the options of TYPO3 again. No
stored value changes.

The column of this extension is shown in no form and read by no template, so
partner pages look the same on both versions.

Affected Installations
======================

Installations with :guilabel:`EXT:academic_partners` on TYPO3 v14. Nothing
changes on TYPO3 v13.

..  index:: Backend, TCA, ext:academic_partners
