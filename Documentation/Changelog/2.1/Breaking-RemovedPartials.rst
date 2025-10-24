.. include:: /Includes.rst.txt

.. _breaking-1748865630:

==========================
Breaking: Removed partials
==========================

Description
===========

Some partials got removed as the templating structure has changed.


Impact
======

Those partials include:

* `Resources/Private/Layouts/Default.html`
* `Resources/Private/Pages/AcademicPartners.html`
* `Resources/Private/Partials/Categories.html`
* `Resources/Private/Partials/Partner/Items.html`
* `Resources/Private/Partials/Partner/SingleItem.html`
* `Resources/Private/Partials/Partnerships/ListItem.html`
* `Resources/Private/Partials/Partnerships/TeaserItem.html`


Affected Installations
======================

TYPO3 instances with extensions overriding those partials of `EXT:academic_partners`.


Migration
=========

Adapt overrides accordingly to the new templating structure.

.. index:: Fluid, Frontend
