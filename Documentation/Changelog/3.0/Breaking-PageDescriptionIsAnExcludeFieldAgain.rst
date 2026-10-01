..  _breaking-1790839520:

========================================================
Breaking: The page description is an exclude field again
========================================================

Description
===========

The description of a partner is the field :sql:`description` of its page, the
field EXT:seo renders as the meta description of a page.
:guilabel:`EXT:academic_partners` defined that column again to label it for
partners. A column added under a name TYPO3 already uses replaces its
definition on every page type, so on every page of the installation the
description

*   was no longer a field an editor group needs in :guilabel:`Allowed fields`,
*   no longer got the prefix TYPO3 adds to it when a page is translated,
*   showed the label of this extension and five rows.

The extension now keeps the definition of TYPO3, and labels the field as the
description of the partner on partner pages only, with five rows as before.

Impact
======

*   The description of a page is an :php:`exclude` field again. An editor
    group needs it in :guilabel:`Allowed fields` to edit it, on partner
    pages as on every other page.
*   A translation of a page gets the prefix of TYPO3 in its description, as
    without this extension.
*   Pages other than partner pages show the label and the size of TYPO3.

No stored value changes.

Affected Installations
======================

Every installation with :guilabel:`EXT:academic_partners` whose editor groups
edit the description of pages or partners without having the field in
:guilabel:`Allowed fields`.

Migration
=========

Add the description of pages to :guilabel:`Allowed fields` of every editor
group that edits it, or to its permission set, see the `permission sets of
academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Integration/PermissionSets.html>`__.

..  index:: Backend, TCA, ext:academic_partners
