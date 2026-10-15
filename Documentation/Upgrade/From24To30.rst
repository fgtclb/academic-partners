..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 of :guilabel:`academic_partners` runs on TYPO3 13 and 14 only. The
partner page renders inside the layout of the site and carries its content
itself, so the content load set of 2.4 is gone. The templates carry speaking
classes in place of the classes of a theme, the map ships no stylesheet and
loads its script as a module, the icons are renamed, and the controller is
final. Work through the sections in order. Skip a step that does not apply to
the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_partners` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires in place of 1.x. Composer updates it
    on its own. A classic installation updates
    :guilabel:`environment_state_manager` before it updates
    :guilabel:`academic_base`.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/category-types` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update, because a
site or a request fails until they are made:

*   A site that depends on `fgtclb/academic-partners-content-load` answers
    every page with HTTP 500, see
    :ref:`upgrade-from-2-4-to-3-0-content-load`.
*   A subclass or an XCLASS of the partner controller, and a subclass of the
    partner repository that declares :php:`findAll()`, stop with a fatal
    error, see :ref:`upgrade-from-2-4-to-3-0-code`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/category-types':'~3.0.0' \
        'fgtclb/academic-partners':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`category_types` and
:guilabel:`academic_partners` there, in this order. Run the commands of this
page with :bash:`typo3/sysext/core/bin/typo3` instead of
:bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension changes no column in 3.0. The comparison applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring.

Version 3.0 adds no upgrade wizard for this extension. The two wizards of 2.4
have nothing left to do on an installation that ran them with 2.4.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. For this extension it reports a dependency on
the removed content load set and an XCLASS of the controller, but not a
subclass that is registered another way. Run it on the staging copy right
after the package update, and fix what it reports before the deployment:

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

..  _upgrade-from-2-4-to-3-0-content-load:

Remove the content load set
---------------------------

The partner page renders the content of its main column from the variable
:typoscript:`partnerContent` of its own page object now. The set
`fgtclb/academic-partners-content-load`, its static template
:guilabel:`Academic Partners: Content load override (academic_partners)` and
the file :file:`Configuration/TypoScript/ContentLoad/setup.typoscript` are
removed, and no academic extension defines
:typoscript:`styles.content.getContent` any more. See
:ref:`breaking-partners-content-load-set-removed`.

#.  Remove `fgtclb/academic-partners-content-load` from the
    :yaml:`dependencies` of every site configuration and of every set of the
    site package, in the same deployment as the update. A site that depends on
    the aggregate `fgtclb/academic-partners` needs no change.
#.  Remove the static template :guilabel:`Academic Partners: Content load
    override (academic_partners)` from every :sql:`sys_template` record, and an
    :typoscript:`@import` of the removed file from the site package.
#.  Move a customisation of :typoscript:`styles.content.getContent` that was
    meant for partner pages to :typoscript:`page.10.variables.partnerContent`,
    inside the condition on the partner page type, see
    :ref:`partner-page-content`.
#.  A template of the site package that renders
    :typoscript:`styles.content.getContent` defines the object itself:

    ..  code-block:: typoscript

        styles.content.getContent < styles.content.get
        styles.content.getContent.select.where = {#colPos}=0

:bash:`academic:upgrade:check` reports a site that depends on the removed set as
:bash:`unavailable-set`, the removed static template as
:bash:`static-template` and an import of the removed file as
:bash:`typoscript-import`.

..  _upgrade-from-2-4-to-3-0-backend-user-groups:

Extend the backend user groups
------------------------------

The description of a page is an exclude field again, on partner pages as on
every other page. Add it to :guilabel:`Allowed fields` of every backend user
group that edits the description of pages or partners, or to its permission
set. See :ref:`breaking-1790839520`.

..  _upgrade-from-2-4-to-3-0-route-enhancers:

Check own route enhancers
-------------------------

The partner list and map answer a filter submission with a redirect to a URL
that carries the selection. A route enhancer of the site for these plugins must
not declare :yaml:`defaults` for the sorting: the redirect for the default
sorting would otherwise end on the page URL without arguments, where the preset
of the content element applies again. Remove such :yaml:`defaults`, or import
the enhancers of :file:`Configuration/Routes/List.yaml` instead, see
:ref:`feature-1790226100` and :ref:`feature-1791043402`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-stylesheets:

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

The stylesheet of the map is removed. Without a stylesheet of the site, the map
has no height and stays invisible. Give the map element a height and take over
the two other rules of the removed file where the theme needs them, see
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

..  _upgrade-from-2-4-to-3-0-icons:

Replace the renamed icons
-------------------------

The icons of this extension are new drawings, registered in a way that follows
the colour scheme of the backend and the colour of the text in the frontend.
Their identifiers changed without an alias, :php:`academic-partners` became five
identifiers. Replace every identifier of 2.4 in TCA, page TSconfig, templates
and code of the site, and register a replacement drawing under the new
identifier. The category type icons keep their identifiers, but render as an
inline :html:`<svg>` in the frontend, so CSS that addressed an :html:`<img>`
addresses the :html:`<svg>` now. See
:ref:`breaking-partners-record-and-category-icons-follow-the-colour-scheme` and
:ref:`important-partners-category-icons-come-from-the-frontend-icon-registry`.

..  _upgrade-from-2-4-to-3-0-map-module:

Load the map script as a module
-------------------------------

The map script is an ES module now, and it imports Leaflet and the marker
cluster plugin as modules as well. A site that uses the shipped templates needs
to do nothing. An override of :file:`Templates/Partner/Map.html` or of the new
partial :file:`Partials/Partner/Map.html`, or a site package that loads the
script itself, replaces the script reference and drops the one to
:file:`map.css`:

..  code-block:: html

    <f:asset.module identifier="@fgtclb/academic-partners/frontend/map.js" />

See :ref:`breaking-ace-392-academic-partners-map-module` and
:ref:`important-1790623331`. The classic files of Leaflet and of the marker
cluster plugin below :file:`Resources/Public/JavaScript/` and
:file:`Resources/Public/Css/` stay until 4.0 for a site that loads them itself.
Move such a reference to the module files, see
:ref:`deprecation-1790623332`.

A page template of the site that shows the map of one partner renders the
partial with the argument :html:`partner` in place of a map of its own, so a
partner without coordinates or with :guilabel:`Show on map` switched off is
left out, see :ref:`important-ace-770-academic-partners-map-partial`.

..  _upgrade-from-2-4-to-3-0-page:

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
*   A path the site set at the key `100` inside the condition on the partner
    page type moves to `50`, or to a key above it to win over the extension.

..  _upgrade-from-2-4-to-3-0-overrides:

Compare template overrides
--------------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Pages/AcademicPartner.html`
        -   Renders the layout of the site and the partials below
            :file:`Partner/Page/`, and the content from
            :html:`{partnerContent -> f:format.raw()}` in place of
            :typoscript:`styles.content.getContent`.
        -   :ref:`upgrade-from-2-4-to-3-0-page`,
            :ref:`upgrade-from-2-4-to-3-0-content-load`
    *   -   :file:`Templates/Partner/Map.html`
        -   Renders the map through the new partial :file:`Partner/Map.html`,
            which loads the map module and passes the map settings of the site
            as data attributes.
        -   :ref:`upgrade-from-2-4-to-3-0-map-module`,
            :ref:`feature-1790618147`
    *   -   :file:`Templates/Partner/List.html`,
            :file:`Templates/Partner/Map.html`,
            :file:`Templates/Partner/PartnershipsList.html`,
            :file:`Templates/Partner/PartnershipsTeaser.html`
        -   Render the header of the content element while
            :typoscript:`renderContentElementHeader` is switched on.
        -   :ref:`feature-1790440962`
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
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`,
            :ref:`upgrade-from-2-4-to-3-0-icons`,
            :ref:`feature-1791035983`
    *   -   :file:`Partials/Partner/DemandCategories.html`
        -   Labels an own category type by its title.
        -   :ref:`feature-1791035983`
    *   -   :file:`Partials/Partner/Header.html`
        -   Takes the class of the heading as :html:`class` in place of
            :html:`positionClass`, and wraps it in
            :html:`<header class="ace-header">`.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`
    *   -   Every file above, and :file:`Partials/Partner/DemandSorting.html`
        -   Speaking classes in place of the classes of a theme.
        -   :ref:`upgrade-from-2-4-to-3-0-stylesheets`

The upgrade check with the extension key and a site reports an override of a
file the extension no longer ships, see
:ref:`upgrade-from-2-4-to-3-0-upgrade-check`.

..  _upgrade-from-2-4-to-3-0-code:

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
*   The FlexForm data structures are one file per plugin now,
    :file:`Configuration/FlexForms/ListSettings.xml` and
    :file:`Configuration/FlexForms/MapSettings.xml`. The files below
    :file:`Configuration/FlexForms/Core12/` and :file:`Core13/` are gone. Code
    of the site that names one of them by path names the new file. The field
    names are unchanged. See
    :ref:`important-partners-flexform-data-structures-moved`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`. Run it again after each
    configuration step, and in the deployment pipeline.
*   The content elements are offered as :guilabel:`Partner List`,
    :guilabel:`Partner Map`, :guilabel:`Partners Linked` and
    :guilabel:`Partner Logo Teaser` now, see
    :ref:`important-content-element-titles-and-descriptions`.
*   Every page of a site answers, none with HTTP 500.
*   A partner page renders inside the layout of the site, with its content,
    its categories and its address.
*   The partner list, the map and the partnerships look as the site intends,
    with the speaking classes, and the map has a height.
*   Editors that edit partner pages can still edit the description.
*   A list or map keeps its default categories of the category types its filter
    does not offer, see
    :ref:`important-partner-list-keeps-default-categories-of-other-types`.
*   Partnerships that lost their page or their role through a workspace
    publish in 2.x: the changelog entry
    :ref:`important-partnerships-keep-their-page-on-publish` carries the
    queries that find them. Select the page or the role again, or add the
    partnership anew.
