.. _important-ace-250-academic-partners:

=======================================================
Important: Extended `PartnerDemand` and demand handling
=======================================================

Description
===========

To support the new "Show hidden records" plugin option, the partner
demand pipeline gained a new transport flag:

* :php:`\FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand` has a new
  :php:`bool $showHiddenRecords` property with
  :php:`setShowHiddenRecords(bool): void` and
  :php:`getShowHiddenRecords(): bool` accessors (default :php:`false`).
* :php:`\FGTCLB\AcademicPartners\Factory\DemandFactory::createDemandObject()`
  now reads :php:`$settings['showHiddenRecords']` and sets the flag on the
  demand object.
* :php:`\FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository::findByDemand()`
  honours the flag: when it is :php:`true`, the query ignores only the
  `disabled` (`hidden`) enable field via the Extbase query settings.

The public method signatures of :php:`DemandFactory::createDemandObject()`
and :php:`PartnerRepository::findByDemand()` are unchanged.

Impact
======

The change is non-breaking: the new flag defaults to :php:`false` and no
existing method signature changed. Projects that build a
:php:`PartnerDemand` themselves can opt in by calling
:php:`setShowHiddenRecords(true)`.

Affected Installations
======================

Only installations that extend or replace the :php:`PartnerDemand` DTO,
the :php:`DemandFactory` or the :php:`PartnerRepository` need to take the
added flag into account. All other installations are unaffected.

.. index:: PHP, ext:academic_partners
