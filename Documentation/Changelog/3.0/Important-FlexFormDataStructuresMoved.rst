..  _important-partners-flexform-data-structures-moved:

=============================================
Important: The FlexForm data structures moved
=============================================

Description
===========

Version 2.x shipped the FlexForm data structure of the :guilabel:`Partner List`
twice, as :file:`Configuration/FlexForms/Core12/ListSettings.xml` and
:file:`Configuration/FlexForms/Core13/ListSettings.xml`, and picked one by the
TYPO3 major version. The :guilabel:`Partner Map` used the same file.

With TYPO3 v12 no longer supported, see
:ref:`breaking-removed-typo3-v12-support`, each content element has one file of
its own:

..  list-table::
    :header-rows: 1

    *   -   Content element
        -   Data structure
    *   -   :guilabel:`Partner List`
        -   :file:`Configuration/FlexForms/ListSettings.xml`
    *   -   :guilabel:`Partner Map`
        -   :file:`Configuration/FlexForms/MapSettings.xml`

The directories :file:`Configuration/FlexForms/Core12/` and
:file:`Configuration/FlexForms/Core13/` are gone. The fields both elements had
keep their sheet and their names, so the stored settings of existing content
elements and page TSconfig that addresses them keep working. The new fields
are described in :ref:`feature-1790272109` and :ref:`feature-1790618147`.

Impact
======

Code or configuration of a site that names one of the former files by path,
in TCA of its own or in a copy of the data structure, names a file that no
longer exists.

Migration
=========

Name the new file of the element instead. A map names
:file:`EXT:academic_partners/Configuration/FlexForms/MapSettings.xml`, not the
file of the list: the fields of the map are only in its own file.

..  index:: Backend, FlexForm, TCA, NotScanned, ext:academic_partners
