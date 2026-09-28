:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships its frontend TypoScript and its backend page TSconfig in
two forms: as TYPO3 **site sets**, and as classic **static templates** plus
**page TSconfig files** that are selected on a page. Both forms read the very
same files, so they configure an installation identically.

Pick one of them per site and stay with it — see
:ref:`Do not combine both <one-mechanism-per-site>` for what happens otherwise.

..  _configuration-components:

What the sets contain
=====================

This extension ships four content elements, so it ships four component sets, one
set for the :typoscript:`styles.content` override described below, and one
aggregate set that depends on all of them.

All four content elements are driven by one Extbase plugin, so they share one
TypoScript block, :typoscript:`plugin.tx_academicpartners`. That block is
shipped once, in :file:`Configuration/TypoScript/`, and every component includes
it. Which component sets a site names therefore decides which content elements
the backend offers, not how much TypoScript is loaded.

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-partners-list`
        -   The :guilabel:`Partners List` content element.
    *   -   `fgtclb/academic-partners-map`
        -   The :guilabel:`Partners Map` content element.
    *   -   `fgtclb/academic-partners-partnerships-list`
        -   The :guilabel:`Partnerships List` content element.
    *   -   `fgtclb/academic-partners-partnerships-teaser`
        -   The :guilabel:`Partnerships Teaser` content element.
    *   -   `fgtclb/academic-partners-content-load`
        -   The :typoscript:`styles.content.getContent` override only. No content
            element, and nothing this extension is otherwise made of — see
            :ref:`The content load override <content-load-override>`.
    *   -   `fgtclb/academic-partners`
        -   Everything above. This is the set to use unless you deliberately
            want a subset, and it is the name this extension published before
            the sets were cut per component — a site configuration that depends
            on it needs no change.

Every content element set depends on `fgtclb/academic-base-ctype-group`, the set
of :guilabel:`EXT:academic_base` that labels the content element group all
academic extensions sort their elements into.

..  _configuration-hidden-by-default:

The content elements are hidden by default
==========================================

:guilabel:`EXT:academic_partners` hides all four of its content elements for the
whole installation and brings them back per component. Whichever of the two
mechanisms below you use, it is what makes an element selectable in the backend
again — without one of them the content element is not offered, and existing
records keep rendering.

..  warning::

    This changed in version 2.4. Before it, all four elements were selectable on
    every page of every installation. Read
    :ref:`Breaking: Site sets and static templates have been restructured
    <breaking-site-sets-and-static-templates-restructured>` before upgrading:
    opening an existing record on a page that does not include the page TSconfig
    of its component can rewrite the type of that record.

What the sets do not control
============================

The page type :guilabel:`Academic partner` (doktype 40) and its backend layout
:guilabel:`AcademicPartner` are **not** part of any set, and enabling or not
enabling a set never changes them.

That is deliberate, not an oversight. Both are values stored on :sql:`pages`
records: a page carries `doktype = 40` and `backend_layout = pagets__AcademicPartner`
long before any site configuration is read. Were they delivered by an opt-in
set, every page tree on a site that does not use that set would show
:guilabel:`[ MISSING LABEL ]` for the layout, the layout could not be picked for
a new page, and the page type would disappear from the page tree wizard.

They are therefore registered installation-wide — the page type in TCA
(:file:`Configuration/TCA/Overrides/pages.php`), the backend layout in the
always-included :file:`Configuration/page.tsconfig` — and stay available on every
site of the installation.

What a set does deliver for that page type is its **frontend rendering**: the
:typoscript:`page` object that picks the Fluid template of the page type is part
of the shared TypoScript block, so a site that includes no set of this extension
renders such a page with whatever its own site package defines.

..  _content-load-override:

The content load override
=========================

:file:`Configuration/TypoScript/ContentLoad/setup.typoscript` redefines
:typoscript:`styles.content.getContent` for the whole site so that it selects
:typoscript:`colPos = 0` only. This is an installation-wide rendering change, it
applies to every page of the site and not only to the pages of this extension,
and three academic extensions ship the same override.

It is therefore a set of its own, `fgtclb/academic-partners-content-load`. The
aggregate set depends on it, so a site on `fgtclb/academic-partners` keeps what
it had; a site that wants the content elements without the override names the
component sets it needs instead of the aggregate.

..  warning::

    The Fluid template of the page type renders
    :typoscript:`styles.content.getContent` through
    :html:`<f:cObject typoscriptObjectPath="styles.content.getContent"/>`, and
    that ViewHelper throws when the path is undefined. A site that opts out of
    this set and still uses the page type has to define
    :typoscript:`styles.content.getContent` itself.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site that should offer the content
elements:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-partners

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that still configures its frontend through
:sql:`sys_template` records, the same files are registered as static templates
and as selectable page TSconfig files.

..  tip::

    On TYPO3 v13 and v14 we recommend the site set — and if you use it, do not
    press the backend button :guilabel:`Create a root TypoScript record` on that
    site. The :sql:`sys_template` record it creates carries the flag
    :guilabel:`Clear` for constants and setup, and that flag discards everything
    the site sets contributed. An installation that is already in that state
    gets its configuration back by selecting the static templates below in that
    very record.

..  _static-typoscript:

Include static TypoScript
-------------------------

Edit the :sql:`sys_template` record of the site root and add the entry to
:guilabel:`Include static (from extensions)`:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Partners: Partners List (academic_partners)`
        -   The TypoScript of the :guilabel:`Partners List` content element.
    *   -   :guilabel:`Academic Partners: Partners Map (academic_partners)`
        -   The same for :guilabel:`Partners Map`.
    *   -   :guilabel:`Academic Partners: Partnerships List (academic_partners)`
        -   The same for :guilabel:`Partnerships List`.
    *   -   :guilabel:`Academic Partners: Partnerships Teaser (academic_partners)`
        -   The same for :guilabel:`Partnerships Teaser`.
    *   -   :guilabel:`Academic Partners: Content load override (academic_partners)`
        -   The :typoscript:`styles.content.getContent` override on its own.
    *   -   :guilabel:`Academic Partners: All components (academic_partners)`
        -   Every component this extension ships, in one entry.
    *   -   :guilabel:`Academic Partners: Shared plugin settings and page
            rendering (academic_partners)`
        -   The shared :typoscript:`plugin.tx_academicpartners` block and the
            :typoscript:`page` object of the page type, on their own. This is
            the entry an installation stored before the configuration was cut
            per component, and it keeps working — but it does not make any
            content element selectable, which the page TSconfig below does, and
            it no longer carries the content load override.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Partners: Partners List (academic_partners)`
        -   Makes the :guilabel:`Partners List` content element selectable, and
            configures its entry in the new content element wizard.
    *   -   :guilabel:`Academic Partners: Partners Map (academic_partners)`
        -   The same for :guilabel:`Partners Map`.
    *   -   :guilabel:`Academic Partners: Partnerships List (academic_partners)`
        -   The same for :guilabel:`Partnerships List`.
    *   -   :guilabel:`Academic Partners: Partnerships Teaser (academic_partners)`
        -   The same for :guilabel:`Partnerships Teaser`.
    *   -   :guilabel:`Academic Partners: All components (academic_partners)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _configuration-list-pagination:

Pagination of the partner list
==============================

The :guilabel:`Partners List` content element can split its partners into
pages. Whether it does, and how many partners a page holds, is set on each
content element, tab :guilabel:`Pagination`:

..  list-table::
    :header-rows: 1

    *   -   Field
        -   Default
        -   Meaning
    *   -   :guilabel:`Enable pagination`
        -   off
        -   Off, the list renders every partner the filter matches.
    *   -   :guilabel:`Results per page`
        -   10
        -   The number of partners on one page.

How many page numbers the navigation links at once is one value for the whole
site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicpartners.pagination.numberOfLinks`
        -   5
        -   The most page numbers the navigation links around the current page.
            Read with `georgringer/numbered-pagination` only; without it, the
            core pagination links every page.

The site setting is declared by the set `fgtclb/academic-partners-list`, so the
site settings editor offers it to a site that depends on that set or on
`fgtclb/academic-partners`. A site configured through static templates sets the
constant instead.

Every page link keeps the active filter and sorting, and a filter submission
starts on page one. The :guilabel:`Partners Map` is never paginated.

..  _configuration-list-filter:

The category filters
====================

The filter form of the :guilabel:`Partners List` and the :guilabel:`Partners
Map` offers one select per category type of the group `partners`. Three settings
change which of them it offers and how, for the whole site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicpartners.filter.categoryTypes`
        -   empty
        -   The category types to offer, in this order, as a comma separated
            list of type identifiers, for example `sdg,region`. Empty
            offers every type that has a category, in the order of the
            category types of the group.
    *   -   :typoscript:`plugin.tx_academicpartners.filter.visibleCount`
        -   0
        -   How many filters the form shows right away. The others follow in a
            :guilabel:`More filters` section the visitor opens, which is open
            already while one of its filters has a value. 0 shows every filter.
    *   -   :typoscript:`plugin.tx_academicpartners.filter.hideDisabledOptions`
        -   0
        -   Leaves out a category no listed partner carries, instead of offering
            it as a disabled option. A selected category is always offered. A
            filter whose categories are all left out still renders, with its
            "All" option only.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicpartners:
        filter:
          categoryTypes: 'sdg,region'
          visibleCount: 1
          hideDisabledOptions: true

The settings are site settings of the aggregate set `fgtclb/academic-partners`
and constants of the same names for a site on static templates. A site that
depends on `fgtclb/academic-partners-list` or `fgtclb/academic-partners-map`
alone gets the defaults, but the site settings editor does not offer the
settings there — both content elements read them, and a set declares settings
only for itself.

A type is offered only when at least one category of that type exists, and an
identifier that is no type of the group is ignored. The settings decide what
the form offers, not what the list accepts: a link that filters by a category
of a type the form does not offer still filters the list.

The "All" option of a filter
----------------------------

The first option of each filter, the one that selects no category, reads the
label :xml:`sys_category.partners.allOptions.<type>` of this extension, and
falls back to :xml:`sys_category.partners.allOptions` ("All options") where a
type has none. The extension ships no label per type; a site adds them in
TypoScript, for every content element of the extension or for one plugin:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicpartners._LOCAL_LANG {
      default.sys_category.partners.allOptions.region = All regions
      de.sys_category.partners.allOptions.region = Alle Regionen
    }

    # Only in the partner map:
    plugin.tx_academicpartners_map._LOCAL_LANG.default.sys_category.partners.allOptions.region = All regions

A language file override works as well, as for any label of this extension:
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` on TYPO3 v13,
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` on TYPO3 v14,
each pointing from
:file:`EXT:academic_partners/Resources/Private/Language/locallang.xlf` to a file
of the site package.

Templates
---------

The partial :file:`Partner/DemandCategories.html` renders the selects from the
variable :html:`{filterTypes}`: :html:`{filterTypes.visible}` and
:html:`{filterTypes.more}` hold the identifiers of the offered types, in their
order, before and behind :guilabel:`More filters`. Where that variable does not
reach the partial — a project controller that overrides :php:`listAction()` or
:php:`mapAction()`, or a template that renders the partial with arguments of its
own instead of :html:`{_all}` — it offers every type with a category, as before,
and the filter types and the visible count have no effect there. To use them,
let the overriding action call the parent action, and pass :html:`filterTypes`
on in a template that renders the partial.

..  versionadded:: 2.4

    Up to 2.3 the form offered every type with a category, all of them right
    away and with one "All" label, and anything else needed an override of the
    partial.

..  _configuration-map:

The partner map
===============

The :guilabel:`Partners Map` content element draws its partners on a map, with
the tiles of OpenStreetMap by default. Where the map is centred, how far it
zooms and where its tiles come from is one configuration for the whole site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
        -   Meaning
    *   -   :typoscript:`plugin.tx_academicpartners.map.centerLatitude`
        -   51.1657
        -   The latitude the map is centred on while it shows no partner, from
            -90 to 90, with a decimal point.
    *   -   :typoscript:`plugin.tx_academicpartners.map.centerLongitude`
        -   10.4515
        -   The longitude of that centre, from -180 to 180, with a decimal
            point.
    *   -   :typoscript:`plugin.tx_academicpartners.map.zoom`
        -   6
        -   The zoom level while the map shows no partner. 0 shows the whole
            world.
    *   -   :typoscript:`plugin.tx_academicpartners.map.maxZoom`
        -   18
        -   How far the map zooms in at most. A map with partners fits itself
            around them, and around a single partner it zooms in up to this
            level. Lower it to keep the surroundings of that partner in view.
    *   -   :typoscript:`plugin.tx_academicpartners.map.padding`
        -   50
        -   The space in pixels between the edge of the map and the outermost
            partners.
    *   -   :typoscript:`plugin.tx_academicpartners.map.tileUrl`
        -   `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`
        -   The URL template the map loads its tiles from, with the placeholders
            `{z}`, `{x}` and `{y}`, and `{s}` for a subdomain.
    *   -   :typoscript:`plugin.tx_academicpartners.map.attribution`
        -   the attribution the map always showed, which credits OpenStreetMap
        -   The attribution the map shows for its tiles. It is HTML, and the
            map renders it as HTML.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin:
      tx_academicpartners:
        map:
          centerLatitude: 47.5162
          centerLongitude: 14.5501
          zoom: 7
          maxZoom: 14

The settings are site settings of the set `fgtclb/academic-partners-map`, so the
site settings editor offers them to a site that depends on that set or on
`fgtclb/academic-partners`. A site configured through static templates sets the
constants instead.

The defaults are the values the map used before they could be configured, and
the map falls back to them for every value it cannot use: an empty value, a
number that is not a number or lies outside its range. The centre is one value,
so a latitude it cannot use discards the longitude as well. An empty tile URL or
attribution uses the default, because a tile server's terms usually require an
attribution. Whether the tile server delivers tiles up to the maximum zoom,
and what its attribution has to say, is up to the site.

Which partners the map draws
----------------------------

The map draws the partners the filter matches that have coordinates and whose
page has :guilabel:`Show on map` switched on, the switch next to the coordinates
of the partner page. The switch is on for a new partner. It is about the map
only: the partner list lists a partner hidden from the map.

The map reads the switch of the partner page in the language it is rendered in.
The switch of a translation follows its default record, as the coordinates do,
so switching a partner off in the default language takes it off the map in every
language. An editor who detaches the switch of a translation sets it for that
language on its own.

The width of the map
--------------------

Each :guilabel:`Partners Map` content element has a tab :guilabel:`Layout` with
the field :guilabel:`Map width`:

..  list-table::
    :header-rows: 1

    *   -   Option
        -   Renders
    *   -   :guilabel:`Content width`, the default
        -   :html:`<div class="academic-partners-map">`, as before.
    *   -   :guilabel:`Full width`
        -   :html:`<div class="academic-partners-map academic-partners-map--full-width">`

The extension ships no style for the class. What full width means depends on the
page layout of the site, so the theme of the site styles it. A content element
saved before the field existed renders at content width.

The files the map loads
-----------------------

The map is drawn with Leaflet and its marker cluster plugin, both built from
their npm packages into
:file:`Resources/Public/JavaScript/vendor/<library>/<version>/`:

..  list-table::
    :header-rows: 1

    *   -   Library
        -   Version
        -   Module
        -   Stylesheets
    *   -   Leaflet
        -   1.9.4
        -   `leaflet`
        -   :file:`leaflet.css`
    *   -   Leaflet.markercluster
        -   1.5.3
        -   `leaflet.markercluster`
        -   :file:`MarkerCluster.css`, :file:`MarkerCluster.Default.css`

The import map of the extension publishes the modules under those names, and the
map module imports them. No global variable is set. The partial
:file:`Partner/Map.html` registers the stylesheets,
:file:`Css/frontend/map.css`, which sizes the map and keeps a site's image rules
off its tiles and markers. The partial names the marker icon of this extension,
in :file:`Resources/Public/Images/Map/`, in the attribute
`data-academic-partners-marker-icon` of the map element, and the markers load
their images from that directory. Without the attribute they show the icon of
Leaflet.

Another extension or the theme that maps `leaflet` as well shares the map's
import map entry: the page loads one of the two Leaflets for every module.

The map on other pages
----------------------

The map is rendered by the partial :file:`Partner/Map.html`, and a template of
the site package can render it too. It takes these arguments:

..  list-table::
    :header-rows: 1

    *   -   Argument
        -   Meaning
    *   -   :html:`partners`
        -   The partners to draw.
    *   -   :html:`partner`
        -   A single partner instead. The partial renders nothing when the
            partner has no coordinates.
    *   -   :html:`map`
        -   The map settings above. Without them the map uses the defaults.

The page of the page type :guilabel:`Academic partner` does not show a map, but
its template has everything a map for the partner of the page needs:
:html:`{partner}`, and the map settings of the site as :html:`{mapSettings}`.
The data processor `partner-data` adds both, for a :typoscript:`FLUIDTEMPLATE`
and for a :typoscript:`PAGEVIEW` page object. It takes the settings from the
site settings and the constants, not from
:typoscript:`plugin.tx_academicpartners.settings.map`, so a value a site sets in
the TypoScript setup of the plugin reaches the content element only. A site
package that shows the location of the partner renders, in its own
:file:`AcademicPartner.html`:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Pages/AcademicPartner.html

    <f:render partial="Partner/Map" arguments="{partner: partner, map: mapSettings}" />

A single partner is where the maximum zoom matters most: the map zooms in on
the partner up to that level.

The partial draws one map per page. Its element ids are fixed, so on a page that
renders it twice, the content element on a partner page that shows a map for
example, only the first map is drawn and the second stays empty.

The settings reach the map as data attributes of the element
:html:`<div id="map">`, which the partial renders:

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   Setting
    *   -   `data-academic-partners-center-lat`
        -   :typoscript:`centerLatitude`
    *   -   `data-academic-partners-center-lng`
        -   :typoscript:`centerLongitude`
    *   -   `data-academic-partners-zoom`
        -   :typoscript:`zoom`
    *   -   `data-academic-partners-max-zoom`
        -   :typoscript:`maxZoom`
    *   -   `data-academic-partners-padding`
        -   :typoscript:`padding`
    *   -   `data-academic-partners-tile-url`
        -   :typoscript:`tileUrl`
    *   -   `data-academic-partners-attribution`
        -   :typoscript:`attribution`
    *   -   `data-academic-partners-marker-icon`
        -   none, the partial writes the URL of the marker icon of this extension

An attribute that is missing, empty or out of range uses the default, and the
two coordinates are used only together.

A project that overrides :file:`Templates/Partner/Map.html` keeps its template.
It renders the map without these attributes, so its map uses the defaults until
it renders the partial or adds the attributes to its own map element. A project
that already ships a partial :file:`Partner/Map.html` of its own overrides the
shipped one. It is rendered with :html:`partners` and :html:`map`.

..  _configuration-content-element-header:

The header of the content elements
==================================

The header and the subheader an editor enters on a :guilabel:`Partners List`,
:guilabel:`Partners Map`, :guilabel:`Partnerships List` or
:guilabel:`Partnerships Teaser` content element are rendered by the content
element layout of the site, as for any other content element. The layouts of
:guilabel:`EXT:fluid_styled_content` and of the bootstrap package do that, and
the plugins render no header of their own.

A site whose content element layout renders no header, because its element
templates render it instead, lets the plugins render it:

..  code-block:: typoscript
    :caption: TypoScript constants

    plugin.tx_academicpartners.renderContentElementHeader = 1

On a site that uses the site set, that is the site setting :guilabel:`Render the
content element header in the plugins` of `fgtclb/academic-partners`. The
templates then render the header partial of :guilabel:`EXT:fluid_styled_content`
above their output, for every header layout except :guilabel:`Hidden`. Do not
switch it on where the layout renders the header: the header then appears twice.

The extension does not require :guilabel:`EXT:fluid_styled_content`. It adds the
partial path of that extension below every other one, so a site package that
ships a :file:`Header/All.html` of its own renders that one instead, and a site
without :guilabel:`EXT:fluid_styled_content` provides the partial that way.

For the header layout :guilabel:`Default`, the partial takes the heading level
from :typoscript:`plugin.tx_academicpartners.settings.defaultHeaderType`, which
is mapped from the constant :typoscript:`styles.content.defaultHeaderType` of
:guilabel:`EXT:fluid_styled_content`. A site that does not include the
TypoScript of :guilabel:`EXT:fluid_styled_content` sets the setting itself;
without it, such a header renders as an empty :html:`<header>` element.

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension that
is the :typoscript:`plugin.tx_academicpartners` constants block: the three Fluid
root paths, the number of page links of the
:ref:`pagination <configuration-list-pagination>`, the settings of
:ref:`the category filters <configuration-list-filter>` and of
:ref:`the partner map <configuration-map>`, and the
:ref:`content element header <configuration-content-element-header>` switch.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.

..  toctree::
   :maxdepth: 5
   :titlesonly:

   Labels/Index
