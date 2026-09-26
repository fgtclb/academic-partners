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

..  important::

    Site sets arrived in TYPO3 v13.1. On **TYPO3 v12 they do nothing at all** —
    the core has no set API, the folder :file:`Configuration/Sets/` is never
    read, and a :yaml:`dependencies` key in a site configuration is stored and
    ignored. On v12, use the static templates and the page TSconfig files
    described below; they deliver exactly the same files.

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

    On TYPO3 v13 we recommend the site set; on TYPO3 v12 this is the only
    mechanism there is. If you use the site set, do not press the backend button
    :guilabel:`Create a root TypoScript record` on that site. The
    :sql:`sys_template` record it creates carries the flag
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
            offers every type that has a category, in the order the types are
            registered in.
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

Site settings exist from TYPO3 v13 on. On TYPO3 v12, set the constants.

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
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`, pointing
from :file:`EXT:academic_partners/Resources/Private/Language/locallang.xlf`
to a file of the site package.

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

..  _one-mechanism-per-site:

Do not combine both
===================

A site that uses the site set **and** the static template reads the shipped
files twice. The site set is applied before the :sql:`sys_template` record, so
the second read happens after the site settings and after
:file:`config/sites/<site>/constants.typoscript` — and it resets every constant
the extension ships a default for back to that default. For this extension that
is the :typoscript:`plugin.tx_academicpartners` constants block: the three Fluid
root paths and the settings of :ref:`the category filters
<configuration-list-filter>`.

Nothing else is damaged: the :guilabel:`Constants` and :guilabel:`Setup` fields
of the :sql:`sys_template` record, the page TSconfig of a page and the page
TSconfig files selected on a page are all applied afterwards and still win. Use
one mechanism per site and the question does not arise.
