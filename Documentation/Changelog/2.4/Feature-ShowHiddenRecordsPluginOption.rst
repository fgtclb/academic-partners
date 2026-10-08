.. _feature-ace-250-academic-partners:

==================================================================
Feature: "Show hidden records" plugin option for the partner lists
==================================================================

Description
===========

A new boolean plugin option **Show hidden records**
(:typoscript:`settings.showHiddenRecords`, checkbox/toggle, default off)
was added to the following plugins:

* **List** (:php:`academicpartners_list`)
* **Map** (:php:`academicpartners_map`)

Both plugins share the single :file:`ListSettings.xml` flexform data
structure, which gains the new toggle.

When the option is enabled, the frontend partner listing includes hidden
(disabled) records, also for visitors without a preview of hidden records.
Only the `hidden` enable column (`disabled`) is ignored; the `deleted`,
`starttime`/`endtime` and `fe_group` restrictions stay in effect.

On a translated page the listing shows the translation of a hidden record,
and follows the fallback type of the site language like for any other
record. The start and end time of the default record keep deciding for its
translation.

The option is core-version-aware and available in both the TYPO3 v12 and
v13 flexform data structures of the plugins.

Impact
======

Editors can now opt in per plugin instance to display hidden partners in
the frontend, for example to preview intentionally hidden records without
changing the global preview settings. The option is off by default, so
existing plugin instances keep their current behaviour.

Affected Installations
======================

All installations using the `EXT:academic_partners` extension starting
with version 2.4. No action is required for existing installations.

.. index:: Backend, Frontend, ext:academic_partners
