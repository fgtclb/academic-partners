..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_partners` per
component and hides its content elements until a site enables them. It also
registers the static template under this extension, where 2.3 registered it
under :guilabel:`academic_programs`, gives the partnerships of a role a sort
column of their own and keeps the map data of translated partner pages in step
with the default language. Work through the sections in order. Skip a step that
does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_partners` 2.4 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.
*   :composer:`fgtclb/category-types` 2.4, the same release as this extension.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/category-types':'~2.4.0' \
        'fgtclb/academic-partners':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796663`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_partners` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-database:

Update the database
===================

Compare the database schema in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`, or on the command line:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

The command creates and changes tables and columns and drops nothing. For this
extension it adds one column and changes one:

..  list-table::
    :header-rows: 1

    *   -   Column
        -   Change
    *   -   :sql:`tx_academicpartners_domain_model_partnership.role_sorting`
        -   Added. The position of a partnership in the list of its role, which
            the first wizard below fills. See
            :ref:`important-partner-role-sorts-its-partnerships`.
    *   -   :sql:`pages.link`
        -   Changed from :sql:`text NOT NULL DEFAULT ''` to
            :sql:`text DEFAULT NULL`. No data is converted. On TYPO3 12 with
            MySQL this makes it possible to create pages again, see
            :ref:`important-1785882500`.

..  _upgrade-from-2-3-to-2-4-wizards:

Run the upgrade wizards
=======================

Both wizards are new in 2.4. They need the columns of the database compare and
do not depend on each other. Run them in this order, in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the command line:

#.  ``academicPartners_seedPartnershipRoleSorting``, :guilabel:`Seed the
    partnership sort order of academic partner roles`. It fills
    :sql:`role_sorting` with the order the role forms show today, the current
    :sql:`sorting` of each partnership with its uid settling ties. It only
    appends what has no position yet, so a second run changes nothing an
    editor arranged. Until it has run, a role form lists its partnerships in
    arbitrary order. See :ref:`important-partner-role-sorts-its-partnerships`.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicPartners_seedPartnershipRoleSorting

#.  ``academicPartners_synchronizePartnerTranslations``, :guilabel:`Synchronize
    academic partner pages with their translations.` It copies the coordinates
    and the switch :guilabel:`Show on map` of every partner page onto its
    translations, once. A translation made before the page was geocoded
    carries an empty coordinate, and one made while the partner was hidden
    from the map keeps it off the map in its language. Translations whose
    fields an editor detached, deleted pages and workspace versions are left
    alone. See :ref:`important-map-shows-only-located-partners` and
    :ref:`important-partner-map-honours-show-on-map`.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicPartners_synchronizePartnerTranslations

Neither wizard has to run again later. Should one have to, mark it undone
first. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``. TYPO3 12 has no such
command: open :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, find the
wizard under :guilabel:`Wizards marked as done` and choose
:guilabel:`Mark undone`.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The configuration of this extension changed shape in 2.4: one component per
content element, a separate component for the
:typoscript:`styles.content.getContent` override, the page TSconfig as a
selectable entry and the folder :file:`Configuration/TsConfig/` spelled
:file:`Configuration/TSconfig/`. The complete list of paths and names is in
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the four content elements
:guilabel:`Partners List`, :guilabel:`Partners Map`, :guilabel:`Partnerships
List` and :guilabel:`Partnerships Teaser` now. The page TSconfig of a component
brings its element back. Enable them on every site that uses them, with one of
the two mechanisms and never with both:

*   **TYPO3 13 with site sets:** the site configuration depends on
    `fgtclb/academic-partners`. The set keeps the name it had in 2.3 and
    includes every component now, so a site that depends on it already needs
    no change. A site may name the component sets instead. It then adds
    `fgtclb/academic-partners-content-load` as well, or defines
    :typoscript:`styles.content.getContent` itself: the page template of the
    page type :guilabel:`Partner` renders that object and fails without it.
*   **TYPO3 12, or TYPO3 13 without site sets:** in the page record of every
    site root, field :guilabel:`Include static Page TSconfig (from extensions)`
    on the tab :guilabel:`Resources`, add :guilabel:`Academic Partners: All
    components (academic_partners)`, or the entries of the components the site
    uses. The entry is new in 2.4, there is nothing to replace.

Existing content elements keep rendering in the frontend either way. In the
backend, an existing element on a page where its component is not enabled
keeps its type when it is saved. :guilabel:`academic_base` 2.4 shows the stored
type as the selected option, followed by :guilabel:`(not enabled on this
page)`. The warning of the changelog entry, that saving such an element writes
another :sql:`CType`, describes a state the 2.4 release of
:guilabel:`academic_base` no longer has.

..  _upgrade-from-2-3-to-2-4-static-template:

Select the static template again
--------------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record, which is every site on TYPO3 12.

Version 2.3 registered the entry :guilabel:`Academic Partners Page Setup` under
the extension key of :guilabel:`academic_programs`. A record that selected it
stores `EXT:academic_programs/Configuration/TypoScript/` and includes the
TypoScript of that extension instead of this one, or nothing at all where
:guilabel:`academic_programs` is not installed. The update does not rewrite the
stored value, and no wizard does: it cannot be told apart from a deliberate
selection of the programs entry. See :ref:`important-1786968600`.

#.  Open the :sql:`sys_template` record of every site root, field
    :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options`.
#.  Add :guilabel:`Academic Partners: All components (academic_partners)`. It
    carries the shared plugin settings, the rendering of the page type
    :guilabel:`Partner` and the :typoscript:`styles.content.getContent`
    override.
#.  Take care of the stored value of 2.3. Where :guilabel:`academic_programs`
    is installed, the field shows it as :guilabel:`Academic Programs: Shared
    plugin settings and page rendering (academic_programs)`. Remove that entry
    unless the site uses :guilabel:`academic_programs`, where it is the entry
    of that extension and stays. Where :guilabel:`academic_programs` is not
    installed, the field does not show the stored value at all, and saving the
    record drops it.

A site that selects component entries instead of :guilabel:`All components`
adds :guilabel:`Academic Partners: Content load override (academic_partners)`
as well, or defines :typoscript:`styles.content.getContent` itself. The page
template of the page type renders that object and fails without it, and so does
any template of the site package that renders it.

With the entry selected, a partner page renders the page template of this
extension and the partnerships of the page for the first time on a site
without site sets. Look at a partner page before you deploy.

..  _upgrade-from-2-3-to-2-4-imports:

Adjust imports of shipped files
-------------------------------

A site package that imports a file of this extension by path checks every
:typoscript:`@import`. An import of a missing file is silent, so a wrong path
shows up as missing configuration, not as an error.

*   :file:`Configuration/TsConfig/` is :file:`Configuration/TSconfig/` now,
    and the wizard file
    :file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig` is
    :file:`Configuration/TSconfig/Full/page.tsconfig`.
*   :file:`Configuration/TypoScript/setup.typoscript` no longer imports the
    :typoscript:`styles.content.getContent` override. Import
    :file:`Configuration/TypoScript/ContentLoad/setup.typoscript` where the
    site needs it. The old file
    :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone.
*   :file:`Configuration/Sets/AcademicPartners/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

..  _upgrade-from-2-3-to-2-4-label-overrides:

Move label overrides
--------------------

The templates read label overrides from the path the TYPO3 documentation
names. An override under :typoscript:`plugin.tx_academic_partners._LOCAL_LANG`
no longer has an effect. Move it to
:typoscript:`plugin.tx_academicpartners._LOCAL_LANG`, or to
:typoscript:`plugin.tx_academicpartners_<plugin>._LOCAL_LANG` for one plugin.
This includes the labels of the filter form. See
:ref:`important-label-overrides-use-the-documented-path` and
:ref:`important-1790383005`.

..  _upgrade-from-2-3-to-2-4-backend-user-groups:

Extend the backend user groups on TYPO3 12
------------------------------------------

The partnership table declares its language column in TCA now, as an exclude
field. On TYPO3 12, add
:sql:`tx_academicpartners_domain_model_partnership:sys_language_uid` to the
allowed excludefields of every backend user group whose members set the
language of a partnership, tab :guilabel:`Access Lists` of the group record.
See :ref:`important-1791547201`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

The stylesheet and the script of the map moved below :file:`frontend/`, the
four Leaflet files keep their paths:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_partners/Resources/Public/Css/map.css`
        -   `EXT:academic_partners/Resources/Public/Css/frontend/map.css`
    *   -   `EXT:academic_partners/Resources/Public/JavaScript/map.js`
        -   `EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js`

A copy of :file:`Templates/Partner/Map.html`, or a site package that loads the
files itself, takes over the new paths. See
:ref:`breaking-map-assets-are-built`.
:file:`Resources/Public/JavaScript/autocomplete.min.js` is removed. Nothing of
the extension loaded it, a site that does ships it in its own site package.
See :ref:`breaking-removed-unused-autocomplete-library`.

Version 2.4 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/Partner/Map.html`
        -   Loads the moved map files, and only while there are partners to
            draw. Without them it renders a message instead of an empty map.
        -   :ref:`breaking-map-assets-are-built`,
            :ref:`important-map-shows-only-located-partners`
    *   -   :file:`Templates/Partner/PartnershipsList.html`,
            :file:`Templates/Partner/PartnershipsTeaser.html`
        -   A partnership is grouped by
            :html:`{partnership.role.uid} == {role.uid}`, otherwise a
            translated page loses partnerships.
        -   :ref:`important-ace-430-academic-partners`
    *   -   :file:`Pages/AcademicPartner.html`
        -   The categories are read from
            :html:`{partner.attributes.allCategoriesByType}`. A copy taken only
            to repair that can be dropped.
        -   :ref:`important-partner-page-renders-categories`
    *   -   :file:`Partials/Partner/DemandCategories.html`
        -   Offers the category types
            :typoscript:`plugin.tx_academicpartners.filter.categoryTypes`
            names, in that order, and puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section with labels of its own.
        -   :ref:`feature-1790383002`,
            :ref:`important-1790383005`
    *   -   :file:`Pages/AcademicPartner.html`,
            :file:`Partials/Partner/DemandCategories.html`,
            :file:`Partials/Partner/DemandSorting.html`,
            :file:`Partials/Partner/Item.html`,
            :file:`Partials/Partner/ItemList.html`,
            :file:`Partials/Partnerships/List/Item.html`,
            :file:`Partials/Partnerships/Teaser/Item.html`
        -   The translations pass the extension name :html:`AcademicPartners`
            instead of :html:`academic_partners`. A copy that keeps the old
            name does not see the label overrides of the previous section.
        -   :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Backend/Partials/PageLayout/Doktype40.html`
        -   Removed. It was never rendered. The page module shows the
            categories of a partner page through :guilabel:`category_types`
            now, which takes an override under
            :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

A site package that shipped :file:`Academicpartner.html` as the page template
of the page type renames it to :file:`AcademicPartner.html`. The extension sets
:typoscript:`page.10.templateName` itself now. See
:ref:`important-1786968000`.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

Code that extends or replaces :php:`PartnerDemand`, the :php:`DemandFactory` or
the :php:`PartnerRepository` takes the new flag :php:`showHiddenRecords` into
account. No signature changed. See :ref:`important-ace-250-academic-partners`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The new content element wizard offers the partner content elements on the
    pages of every site that uses them, and an existing element opens with its
    type.
*   A partner page renders its template, its content, its partnerships and its
    categories, and its page module shows the categories of the page.
*   The map leaves out partners without coordinates and partners whose
    :guilabel:`Show on map` is switched off, in every language. A site where
    editors switched partners off sees them disappear from the map now.
*   A role form lists its partnerships in the order it showed before the
    update, and saving a role no longer changes the order on a partner page.
*   A list or map that hides a part of its form keeps the preset of that part
    when a visitor uses the rest of the form, see
    :ref:`important-hidden-partner-form-parts-keep-their-presets`. A sorting
    that was hidden only to keep the default categories can be shown again.
*   Translated partnerships: the changelog entries
    :ref:`important-translated-partnerships-keep-their-role` and
    :ref:`important-1791547201` carry queries for translations that point at a
    translated role, duplicates and wrong translation parents. Delete the
    duplicates first, then save the default language records.
*   TYPO3 12: a partnership whose partner page was hidden or timed may have
    lost its partner on an earlier save. Choose the partner again, the select
    offers such partners now. See :ref:`important-ace-672-academic-partners`.
