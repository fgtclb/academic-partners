.. _important-partner-list-keeps-default-categories-of-other-types:

===============================================================
Important: A filter keeps the default categories of other types
===============================================================

Description
===========

A selection submitted to the partner list or the partner map replaced all of
its :guilabel:`Default categories`, also those of a category type the form did
not offer. The form offers the category types
:typoscript:`plugin.tx_academicpartners.filter.categoryTypes` names, so an
element preset to a category of a type left out there lost that category as
soon as the visitor filtered by another type.

A submitted selection now replaces the default categories of the category types
the form offers, and the default categories of every other type stay. Choosing
"All" in a select still clears the default category of that type. A link that
carries a category filter, as the address of a filtered list, the active filter
tags and the page links do, is still the whole selection.

A list that hides its category filter keeps all its default categories, see
:ref:`important-hidden-partner-form-parts-keep-their-presets`.

Impact
======

*   A list or map preset to a category of a type the filter does not offer
    keeps that category when the visitor filters, and the address of the
    filtered list carries it.
*   A list or map whose filter offers the types of all its default categories
    behaves as before.
*   Behaviour is identical on TYPO3 v13 and v14.

.. index:: Frontend, ext:academic_partners
