.. _important-content-element-titles-and-descriptions:

============================================================
Important: Content elements have new titles and descriptions
============================================================

Description
===========

The content elements of this extension have new titles and new descriptions, in
English and in German. They follow one wording that all academic extensions
share now. An editor sees the title in the new content element wizard and in
the type field of a content element, and the description below the title in the
wizard.

..  list-table::
    :header-rows: 1

    *   -   Content type
        -   Title until now
        -   Title now
        -   Description now
    *   -   :typoscript:`academicpartners_list`
        -   :guilabel:`Partners List` (German :guilabel:`Partnerliste`)
        -   :guilabel:`Partner List` (German :guilabel:`Partner Liste`)
        -   Displays a sortable and filterable list of partners.
    *   -   :typoscript:`academicpartners_map`
        -   :guilabel:`Partners Map` (German :guilabel:`Partnerkarte`)
        -   :guilabel:`Partner Map` (German :guilabel:`Partner Karte`)
        -   Displays a sortable and filterable list of partners on an OpenStreetMap map.
    *   -   :typoscript:`academicpartners_partnershipslist`
        -   :guilabel:`Partnerships List` (German :guilabel:`Partnerschaften als Liste`)
        -   :guilabel:`Partners Linked` (German :guilabel:`Partner verknüpfte`)
        -   Lists partners linked to the page.
    *   -   :typoscript:`academicpartners_partnershipsteaser`
        -   :guilabel:`Partnerships Teaser` (German :guilabel:`Partnerschaften als Teaser`)
        -   :guilabel:`Partner Logo Teaser` (German :guilabel:`Partner Logo Teaser`)
        -   Lists logos of selected partners.

Impact
======

Editors see the new titles and descriptions. The content types, the label keys
and their files did not change. A site that replaces a title or a description,
with page TSconfig of the wizard, with
:typoscript:`TCEFORM.tt_content.CType.altLabels` or with a language file
override, keeps its own text.

Affected Installations
======================

Every installation that offers a content element of this extension to its
editors. Nothing has to be migrated.

.. index:: Backend, TSConfig, ext:academic_partners
