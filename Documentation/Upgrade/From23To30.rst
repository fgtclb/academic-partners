..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_partners` 2.3 straight
to 3.0, without the step to 2.4. Version 2.4 adds a database column, two
upgrade wizards, selectable content elements and a static template of its own.
Version 3.0 runs on TYPO3 13 and 14 only, renders the partner page inside the
layout of the site, carries speaking classes, ships no map stylesheet, renames
the icons and makes the controller final. The page leaves out the 2.4 states
that 3.0 replaces: the map files below :file:`frontend/`, see
:ref:`breaking-map-assets-are-built`, and the content load component, which the
:ref:`2.4 changelog entry on the restructured configuration
<breaking-site-sets-and-static-templates-restructured>` names. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_partners` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site or a request fails until they are made:

*   A site without site sets includes the page TSconfig of the components, or
    the content elements are not offered, see
    :ref:`upgrade-from-2-3-to-3-0-content-elements`.
*   A site with a :sql:`sys_template` record selects the static template
    again, or partner pages render without the TypoScript of this extension,
    see :ref:`upgrade-from-2-3-to-3-0-static-template`.
*   A subclass or an XCLASS of the partner controller, and a subclass of the
    partner repository that declares :php:`findAll()`, stop with a fatal
    error, see :ref:`upgrade-from-2-3-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete when the installation also has
:guilabel:`academic_jobs`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` or :guilabel:`academic_projects`. Their
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. Move to TYPO3 14.3.7 or later
once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-partners':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_partners` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The command creates and changes tables and columns and drops nothing. For this
extension it adds one column and changes one, both since 2.4:

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
            :sql:`text DEFAULT NULL`. No data is converted. See
            :ref:`important-1785882500`.

The comparison also applies the changes of the index definitions TYPO3
13.4.35 and 14.3.7 bring.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

Both wizards are new since 2.4. They need the columns of the database
comparison and do not depend on each other. Run them in this order, in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the command line:

#.  ``academicPartners_seedPartnershipRoleSorting``, :guilabel:`Seed the
    partnership sort order of academic partner roles`. It fills
    :sql:`role_sorting` with the order the role forms show today, the current
    :sql:`sorting` of each partnership with its uid settling ties. Until it has
    run, a role form lists its partnerships in arbitrary order.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicPartners_seedPartnershipRoleSorting

#.  ``academicPartners_synchronizePartnerTranslations``, :guilabel:`Synchronize
    academic partner pages with their translations.` It copies the coordinates
    and the switch :guilabel:`Show on map` of every partner page onto its
    translations, once. Translations whose fields an editor detached, deleted
    pages and workspace versions are left alone. See
    :ref:`important-map-shows-only-located-partners` and
    :ref:`important-partner-map-honours-show-on-map`.

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicPartners_synchronizePartnerTranslations

Neither wizard has to run again later. A wizard that TYPO3 recorded as done is
offered again once it is marked undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The configuration of this extension is delivered per component since 2.4: one
component per content element, the page TSconfig as a selectable entry and the
folder :file:`Configuration/TsConfig/` spelled :file:`Configuration/TSconfig/`.
The complete list of paths and names is in the 2.4 changelog entry the
introduction of this page links. The content load component that entry names
was removed again in 3.0, see :ref:`upgrade-from-2-3-to-3-0-content`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports an XCLASS of
the controller and a stored path of 2.3 that no longer exists. Run it on the
staging copy right after the package update, and again once the steps of this
section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the override folders of the
site with the shipped files and reports an override of a file the extension no
longer ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_partners --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-content-elements:

Make the content elements selectable
------------------------------------

The page TSconfig every installation includes hides the four content elements
:guilabel:`Partner List`, :guilabel:`Partner Map`, :guilabel:`Partners Linked`
and :guilabel:`Partner Logo Teaser`. The page TSconfig of a component brings
its element back. Enable them on every site that uses them, with one of the two
mechanisms and never with both:

*   **With site sets:** the site configuration depends on
    `fgtclb/academic-partners`. The set keeps the name it had in 2.3 and
    includes every component, so a site that depends on it already needs no
    change. A site may name the component sets instead.
*   **Without site sets:** in the page record of every site root, field
    :guilabel:`Include static Page TSconfig (from extensions)` on the tab
    :guilabel:`Resources`, add :guilabel:`Academic Partners: All components
    (academic_partners)`, or the entries of the components the site uses.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-static-template:

Select the static template again
--------------------------------

This step concerns sites that include the TypoScript through a
:sql:`sys_template` record.

Version 2.3 registered the entry :guilabel:`Academic Partners Page Setup` under
the extension key of :guilabel:`academic_programs`. A record that selected it
stores `EXT:academic_programs/Configuration/TypoScript/` and includes the
TypoScript of that extension instead of this one, or nothing at all where
:guilabel:`academic_programs` is not installed. The update does not rewrite the
stored value, and no wizard does. See :ref:`important-1786968600`.

#.  Open the :sql:`sys_template` record of every site root, field
    :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options`.
#.  Add :guilabel:`Academic Partners: All components (academic_partners)`. It
    carries the shared plugin settings and the rendering of the page type
    :guilabel:`Partner`.
#.  Take care of the stored value of 2.3. Where :guilabel:`academic_programs`
    is installed, the field shows it as :guilabel:`Academic Programs: Shared
    plugin settings and page rendering (academic_programs)`. Remove that entry
    unless the site uses :guilabel:`academic_programs`, where it is the entry
    of that extension and stays. Where :guilabel:`academic_programs` is not
    installed, the field does not show the stored value at all, and saving the
    record drops it.

With the entry selected, a partner page renders the page template of this
extension for the first time on a site without site sets. Look at a partner
page before you deploy.

..  _upgrade-from-2-3-to-3-0-imports:

Adjust imports of shipped files
-------------------------------

A site package that imports a file of this extension by path checks every
:typoscript:`@import`. An import of a missing file is silent, so a wrong path
shows up as missing configuration, not as an error.

*   :file:`Configuration/TsConfig/` is :file:`Configuration/TSconfig/` now,
    and the wizard file
    :file:`Configuration/TsConfig/Wizards/NewContentElement.tsconfig` is
    :file:`Configuration/TSconfig/Full/page.tsconfig`.
*   :file:`Configuration/TypoScript/Content/ContentLoad.typoscript` is gone,
    without a successor, see the next section.
*   :file:`Configuration/Sets/AcademicPartners/constants.typoscript` and
    :file:`setup.typoscript` are gone. Import the files of
    :file:`Configuration/TypoScript/` instead.

:bash:`academic:upgrade:check` reports an import of a missing file in a
TypoScript record as :bash:`typoscript-import` and in page TSconfig as
:bash:`tsconfig-import`.

..  _upgrade-from-2-3-to-3-0-content:

Replace the global content object
---------------------------------

Version 2.3 defined :typoscript:`styles.content.getContent` for every page of
the site, through :file:`Configuration/TypoScript/setup.typoscript`, and the
partner page rendered it. In 3.0 the partner page renders the content of its
main column from the variable :typoscript:`partnerContent` of its own page
object, and no academic extension defines
:typoscript:`styles.content.getContent` any more. See
:ref:`breaking-partners-content-load-set-removed`.

*   Move a customisation of :typoscript:`styles.content.getContent` that was
    meant for partner pages to :typoscript:`page.10.variables.partnerContent`,
    inside the condition on the partner page type, see
    :ref:`partner-page-content`.
*   A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

..  _upgrade-from-2-3-to-3-0-label-overrides:

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

..  _upgrade-from-2-3-to-3-0-backend-user-groups:

Extend the backend user groups
------------------------------

The description of a page is an exclude field again, on partner pages as on
every other page. Add it to :guilabel:`Allowed fields` of every backend user
group that edits the description of pages or partners, or to its permission
set. See :ref:`breaking-1790839520`.

The partnership table declares its language column in TCA as an exclude field
since 2.4. A backend user group whose members set the language of a
partnership needs
:sql:`tx_academicpartners_domain_model_partnership:sys_language_uid` in its
allowed excludefields, tab :guilabel:`Access Lists` of the group record. A site
that ran 2.3 on TYPO3 12 adds it. See :ref:`important-1791547201`.

..  _upgrade-from-2-3-to-3-0-route-enhancers:

Check own route enhancers
-------------------------

The partner list and map answer a filter submission with a redirect to a URL
that carries the selection. A route enhancer of the site for these plugins must
not declare :yaml:`defaults` for the sorting: the redirect for the default
sorting would otherwise end on the page URL without arguments, where the preset
of the content element applies again. Remove such :yaml:`defaults`, or import
the enhancers of :file:`Configuration/Routes/List.yaml` instead, see
:ref:`feature-1790226100` and :ref:`feature-1791043402`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-stylesheets:

Style the new markup
--------------------

The templates of this extension carry speaking classes now, such as `ace-item`,
`ace-list`, `ace-attribute`, `ace-label` and `ace-value`, in place of the card,
list group and utility classes of a theme. The extension ships no styles for
them. Move the selectors of the site stylesheet from the old classes to the new
ones, the changelog entry lists them side by side, see
:ref:`breaking-partners-speaking-frontend-classes`. :ref:`styling` shows the
markup of every view and links the stylesheet partial the development instances
of the academic extensions use, :ref:`styling-example`.

The stylesheet of the map, :file:`Resources/Public/Css/map.css` in 2.3, is
removed. Without a stylesheet of the site, the map has no height and stays
invisible. Give the map element a height and take over the two other rules of
the removed file where the theme needs them, see
:ref:`breaking-partner-map-ships-no-stylesheet`:

..  code-block:: css

    #map {
        height: 500px;
    }

The images of the partner list, the partnerships and the partner page render
as a :html:`<picture>` through the image partial of :guilabel:`academic_base`.
CSS that selects the image as a direct child of its container adjusts to the
wrapper. A plugin view whose partial root paths the site replaced completely
needs the path of :guilabel:`academic_base` below its own:

..  code-block:: typoscript

    plugin.tx_academicpartners.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

See :ref:`breaking-partners-images-render-as-picture`.

..  _upgrade-from-2-3-to-3-0-icons:

Replace the renamed icons
-------------------------

The icons of this extension are new drawings, registered in a way that follows
the colour scheme of the backend and the colour of the text in the frontend.
Their identifiers changed without an alias, :php:`academic-partners` became five
identifiers. Replace every identifier of 2.3 in TCA, page TSconfig, templates
and code of the site, and register a replacement drawing under the new
identifier. The category type icons keep their identifiers, but render as an
inline :html:`<svg>` in the frontend, so CSS that addressed an :html:`<img>`
addresses the :html:`<svg>` now. See
:ref:`breaking-partners-record-and-category-icons-follow-the-colour-scheme` and
:ref:`important-partners-category-icons-come-from-the-frontend-icon-registry`.

..  _upgrade-from-2-3-to-3-0-map-module:

Load the map script as a module
-------------------------------

Version 2.3 loaded :file:`Resources/Public/Css/map.css` and
:file:`Resources/Public/JavaScript/map.js` as a classic script, next to the
classic files of Leaflet and of the marker cluster plugin. In 3.0 the map
script is an ES module, it imports Leaflet and the marker cluster plugin as
modules, and the map stylesheet is gone. A site that uses the shipped templates
needs to do nothing. An override of :file:`Templates/Partner/Map.html`, of the
new partial :file:`Partials/Partner/Map.html`, or a site package that loads the
script itself, replaces the script references and drops the one to
:file:`map.css`:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />

See :ref:`breaking-ace-392-academic-partners-map-module` and
:ref:`important-1790623331`. The classic files of Leaflet and of the marker
cluster plugin stay until 4.0 for a site that loads them itself. Move such a
reference to the module files, see :ref:`deprecation-1790623332`.

:file:`Resources/Public/JavaScript/autocomplete.min.js` is removed. Nothing of
the extension loaded it, a site that does ships it in its own site package. See
:ref:`breaking-removed-unused-autocomplete-library`.

The map leaves out partners without coordinates and partners whose
:guilabel:`Show on map` is switched off. A page template of the site that shows
the map of one partner renders the partial with the argument :html:`partner` in
place of a map of its own, see
:ref:`important-ace-770-academic-partners-map-partial`.

..  _upgrade-from-2-3-to-3-0-page:

Render partner pages in the site layout
---------------------------------------

The page template of the partner page type renders the section :html:`Main` of
the layout :file:`Default` of the site package, through five partials below
:file:`Partner/Page/`. A partner page shows the header, the navigation and the
footer of the site now. See
:ref:`breaking-partner-page-renders-inside-the-site-layout`.

*   A site package whose layout for partner pages has another name sets
    :typoscript:`plugin.tx_academicpartners.page.layout`, see
    :ref:`partner-page-layout`.
*   An override of :file:`Pages/AcademicPartner.html` made to change one part
    moves that change to the matching partial below :file:`Partner/Page/`, see
    :ref:`partner-page-partials`, and the override goes.
*   A site package that shipped :file:`Academicpartner.html` as the page
    template of the page type renames it to :file:`AcademicPartner.html`. The
    extension sets :typoscript:`page.10.templateName` itself. See
    :ref:`important-1786968000`.

..  _upgrade-from-2-3-to-3-0-overrides:

Compare template overrides
--------------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicPartner.html`
        -   Renders the layout of the site and the partials below
            :file:`Partner/Page/`, the content from
            :html:`{partnerContent -> f:format.raw()}` in place of
            :typoscript:`styles.content.getContent`, and the categories from
            :html:`{partner.attributes.allCategoriesByType}`.
        -   :ref:`upgrade-from-2-3-to-3-0-page`,
            :ref:`upgrade-from-2-3-to-3-0-content`,
            :ref:`important-partner-page-renders-categories`
    *   -   :file:`Templates/Partner/Map.html`
        -   Renders the map through the new partial :file:`Partner/Map.html`,
            which loads the map module and passes the map settings of the site
            as data attributes.
        -   :ref:`upgrade-from-2-3-to-3-0-map-module`,
            :ref:`feature-1790618147`
    *   -   :file:`Templates/Partner/PartnershipsList.html`,
            :file:`Templates/Partner/PartnershipsTeaser.html`
        -   A partnership is grouped by
            :html:`{partnership.role.uid} == {role.uid}`, otherwise a
            translated page loses partnerships. The partnerships without a
            role are rendered after the role groups, from the new variable
            :html:`{partnershipsWithoutRole}`.
        -   :ref:`important-ace-430-academic-partners`,
            :ref:`important-ace-917-academic-partners`
    *   -   :file:`Templates/Partner/List.html`,
            :file:`Templates/Partner/Map.html`,
            :file:`Templates/Partner/PartnershipsList.html`,
            :file:`Templates/Partner/PartnershipsTeaser.html`
        -   Render the header of the content element while
            :typoscript:`renderContentElementHeader` is switched on.
        -   :ref:`feature-1790440962`
    *   -   :file:`Partials/Partner/DemandCategories.html`
        -   Offers the category types
            :typoscript:`plugin.tx_academicpartners.filter.categoryTypes`
            names, in that order, puts the ones after
            :typoscript:`filter.visibleCount` into a :guilabel:`More filters`
            section, and labels an own category type by its title.
        -   :ref:`feature-1790383002`,
            :ref:`feature-1791035983`
    *   -   :file:`Partials/Partner/SortingAndFilters.html`
        -   Renders the new partials :file:`Partner/ActiveFilters.html` and
            :file:`Partner/ResultCount.html`.
        -   :ref:`feature-1790946474`
    *   -   :file:`Partials/Partner/ItemList.html`
        -   Renders one page of partners and the new partial
            :file:`Partner/Pagination.html` while the pagination is enabled.
        -   :ref:`feature-1790272109`
    *   -   :file:`Partials/Partner/Item.html`,
            :file:`Partials/Partnerships/List/Item.html`,
            :file:`Partials/Partnerships/Teaser/Item.html`
        -   Render the image through :file:`Academic/Image.html` of
            :guilabel:`academic_base`, the category icons with the
            :html:`ab:icon` ViewHelper and the title of an own category type.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-3-to-3-0-icons`,
            :ref:`feature-1791035983`
    *   -   :file:`Partials/Partner/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`, and wraps it in
            :html:`<header class="ace-header">`.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`
    *   -   Every file above, and :file:`Partials/Partner/DemandSorting.html`
        -   Speaking classes in place of the classes of a theme. The
            translations pass the extension name :html:`AcademicPartners`
            instead of :html:`academic_partners`, otherwise they miss the
            label overrides of the configuration step.
        -   :ref:`upgrade-from-2-3-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-3-to-3-0-label-overrides`
    *   -   :file:`Backend/Partials/PageLayout/Doktype40.html`
        -   Removed. It was never rendered. The page module shows the
            categories of a partner page through :guilabel:`category_types`,
            which takes an override under
            :typoscript:`templates.fgtclb/category-types.<key>`.
        -   :ref:`important-1789660802`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, see
:ref:`upgrade-from-2-3-to-3-0-upgrade-check`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

*   :php:`\FGTCLB\AcademicPartners\Controller\PartnerController` is final. A
    subclass, and an XCLASS of it, stop with a fatal error. Remove them, and
    the :php:`configurePlugin()` call or the XCLASS registration that points at
    them, in the same deployment as the update. Move what they did to a
    listener of the partner events or of the plugin view event, see
    :ref:`breaking-1791043406` and :ref:`developers`.
*   :php:`PartnerRepository::findAll()` declares
    :php:`QueryResultInterface` as its return type. A subclass that overrides
    it with :php:`?QueryResult` stops with a fatal error. Declare the new
    type, see :ref:`breaking-partner-repository-find-all-return-type`.
*   Code that extends or replaces :php:`PartnerDemand`, the
    :php:`DemandFactory` or the :php:`PartnerRepository` takes the flag
    :php:`showHiddenRecords` into account, new since 2.4. See
    :ref:`important-ace-250-academic-partners`.
*   The FlexForm data structures are one file per plugin now,
    :file:`Configuration/FlexForms/ListSettings.xml` and
    :file:`Configuration/FlexForms/MapSettings.xml`. The files below
    :file:`Configuration/FlexForms/Core12/` and :file:`Core13/` are gone. Code
    of the site that names one of them by path names the new file. The field
    names are unchanged. See
    :ref:`important-partners-flexform-data-structures-moved`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   The new content element wizard offers :guilabel:`Partner List`,
    :guilabel:`Partner Map`, :guilabel:`Partners Linked` and
    :guilabel:`Partner Logo Teaser` on the pages of every site that uses them,
    see :ref:`important-content-element-titles-and-descriptions`, and an
    existing element opens with its type.
*   A partner page renders inside the layout of the site, with its content,
    its categories and its address, and its page module shows the categories
    of the page.
*   The partner list, the map and the partnerships look as the site intends,
    with the speaking classes, and the map has a height. The map leaves out
    partners without coordinates and partners whose :guilabel:`Show on map` is
    switched off, in every language.
*   A role form lists its partnerships in the order it showed before the
    update, and saving a role no longer changes the order on a partner page.
*   A list or map that hides a part of its form keeps the preset of that part
    when a visitor uses the rest of the form, see
    :ref:`important-hidden-partner-form-parts-keep-their-presets`. A sorting
    that was hidden only to keep the default categories can be shown again.
*   A page whose partnerships partly have a role lists the partnerships
    without a role after the role groups, and so the partnerships of a hidden
    or deleted role. A role given to a partnership only to keep it visible can
    be removed again. Hide a partnership to take it off the page, see
    :ref:`important-ace-917-academic-partners`.
*   A list or map keeps its default categories of the category types its filter
    does not offer, see
    :ref:`important-partner-list-keeps-default-categories-of-other-types`.
*   Editors that edit partner pages can still edit the description.
*   Translated partnerships: the changelog entries
    :ref:`important-translated-partnerships-keep-their-role` and
    :ref:`important-1791547201` carry queries for translations that point at a
    translated role, duplicates and wrong translation parents. Delete the
    duplicates first, then save the default language records.
*   Partnerships that lost their page or their role through a workspace
    publish in 2.x: the changelog entry
    :ref:`important-partnerships-keep-their-page-on-publish` carries the
    queries that find them.
*   An installation that ran on TYPO3 12: a partnership whose partner page was
    hidden or timed may have lost its partner on an earlier save. Choose the
    partner again, see :ref:`important-ace-672-academic-partners`.
