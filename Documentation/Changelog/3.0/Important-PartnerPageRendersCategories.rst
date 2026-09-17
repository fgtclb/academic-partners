.. _important-partner-page-renders-categories:

=======================================================
Important: The partner page type renders its categories
=======================================================

Description
===========

A page of the partner page type can carry categories of the category types
this extension registers, and its page template has always contained a block
that lists them, grouped by category type. That block never appeared.

It read the categories as :html:`{partner.categories.allCategoriesByType}`,
and the model behind :html:`{partner}` has no such property — it exposes the
collection as :php:`getAttributes()`. Fluid resolves an unknown path to
:php:`null` without raising anything, so the surrounding :html:`<f:if>` was
false on every partner page and the whole list was skipped. The list and
teaser partials of this extension already read the working path.

The page template now reads it as well:

..  code-block:: html

    <f:for each="{partner.attributes.allCategoriesByType}" as="categories" key="type">

Impact
======

A partner page with at least one category assigned now shows its category
types and the categories of each, in the markup the template already
contained — for example :guilabel:`Region` followed by
:guilabel:`Rhine-Main Area`.

A category type without an assigned category produces no entry, and a partner
page without categories renders exactly as before.

Affected Installations
======================

All installations that render partner pages with the page template shipped by
this extension and assign categories to them.

An installation that overrides
:file:`Resources/Private/Pages/AcademicPartner.html` is not affected. A copy
that was taken only to repair this defect can be dropped, as long as it
carries no other change.

.. index:: Fluid, Frontend, ext:academic_partners
