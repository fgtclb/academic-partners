..  _feature-plugin-view-event:

===================================================
Feature: The plugins dispatch the plugin view event
===================================================

Description
===========

The partner list, the partner map, the partnerships list and the partnerships
teaser of :guilabel:`academic_partners` dispatch
:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` of
:guilabel:`academic_base`, once each time they render, after assigning their
own variables. A listener adds a variable to their templates without replacing
the controller.

The context of the event names the extension :php:`AcademicPartners` and the
plugin names :php:`List`, :php:`Map`, :php:`PartnershipsList` and
:php:`PartnershipsTeaser`, which a listener checks for the plugin it means.

The partner list and map dispatch it after their list event,
:php:`ModifyPartnerListEvent`, which stays.

The event, with an example listener, is described in the `changelog of
academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/3.0/Feature-ModifyPluginViewEvent.html>`__.

Impact
======

A project adds a variable to the templates of these plugins with an event
listener instead of a subclass or a copy of the controller. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_partners
