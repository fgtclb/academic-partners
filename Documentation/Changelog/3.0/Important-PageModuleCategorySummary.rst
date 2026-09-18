.. _important-1789660801:

====================================================
Important: The page module shows the page categories
====================================================

Description
===========

A partner page (page type 40) carries its categories in the page properties,
where an editor has to open a form to see them. The page module shows them now
as well, in a table above the content grid: one row per category type of the
:yaml:`partners` group - region, partner type, collaboration type and SDG -
with the categories assigned to that page.

A type the page carries no category of is listed with a `Not set` note, and a
category that is assigned and switched off is listed with the hidden overlay.

The extension has shipped that table since its first release and has never
shown it.

:file:`Resources/Private/Backend/Partials/PageLayout/Doktype40.html` was added
as a partial from the start, in February 2025, together with a
:typoscript:`module.tx_backend.view.partialRootPaths` registration for the
directory holding it - the shape :php:`EXT:academic_programs` had been left in
by a rename two years earlier. **No template of** :php:`EXT:backend`
**renders a** :file:`PageLayout/Doktype*` **partial**, so the file has never
been read.

:file:`PageLayout/Doktype*` is not a core convention and never was. The Fluid
page module arrived in TYPO3 v10.3, and the changelog that introduced it lists
every template and partial it ships; the only per-record convention it defines
is :file:`PageLayout/Record/<CType>/{Header,Footer,Preview}`, keyed by the
content type of a record and not by the type of a page. There has been no
version of TYPO3 in which that file name resolved.

TYPO3 v12 later removed the :typoscript:`module.tx_backend.view` mechanism
altogether (v12.0, Breaking: #96812), and the replacement registration in
:file:`Configuration/page.tsconfig`

..  code-block:: typoscript

    templates.typo3/cms-backend.academic-partners = fgtclb/academic-partners:Resources/Private/Backend

kept pointing at the same unrendered partial.

A second defect hid behind the first: the partial translated
:php:`sys_category.academic_partners.{type}`, a key that exists in no XLF
file of this extension, so every type label would have been empty even if
something had rendered it.

The partial and the registration are removed. The summary is rendered by
:php:`EXT:category_types` now, which this extension already depends on, and
asked for by an event listener on
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent`.

Impact
======

*   The page module of a partner page shows the categories of that page.
    No other page type is affected: the page module of a standard page
    renders exactly what it rendered before.

*   The type labels are the titles the category types are registered with in
    :file:`Configuration/CategoryTypes.yaml`, so a type an installation adds to
    the :yaml:`partners` group is labelled too.

*   The override key changed. An installation that registered

    ..  code-block:: typoscript

        templates.typo3/cms-backend.academic-partners = my-vendor/my-site:Resources/Private/Backend

    to replace :file:`PageLayout/Doktype40.html` was overriding a file that
    had never been rendered, so it saw no summary either.
    It registers

    ..  code-block:: typoscript

        templates.fgtclb/category-types.my-site = my-vendor/my-site:Resources/Private/Backend

    instead now, and puts its markup in
    :file:`Resources/Private/Backend/Templates/PageCategorySummary.html`.

Affected Installations
======================

Every installation using this extension sees the summary after the update -
that is the point of the change. Only an installation that overrode the removed
partial has anything to do, and what it had was not in effect.

References
==========

*   TYPO3 v10.3 changelog `Feature: #90348 - Fluid-based replacement for
    PageLayoutView
    <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/10.3/Feature-90348-NewFluid-basedReplacementForPageLayoutView.html>`__ - the
    templates and partials the page module ships, and the one per-record naming
    convention it defines.

*   TYPO3 v12.0 changelog `Breaking: #96812 - No Frontend TypoScript based
    template overrides in the backend
    <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-96812-NoFrontendTypoScriptBasedTemplateOverridesInTheBackend.html>`__ - why the
    :typoscript:`module.tx_backend.view` registration stopped existing and what
    replaced it.

*   The chapter :guilabel:`For Developers` of :php:`EXT:category_types`,
    section :guilabel:`The page module category summary`, describes the
    template, its variables and the override key.

.. index:: Backend, TSConfig, Fluid, NotScanned
