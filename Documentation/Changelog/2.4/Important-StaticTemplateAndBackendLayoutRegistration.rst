..  _important-1786968600:

==============================================================================
Important: The static template and the backend layout are registered correctly
==============================================================================

Description
===========

Two registrations of this extension did not reach an installation.

**The static template was registered under the wrong extension key.**
:file:`Configuration/TCA/Overrides/sys_template.php` passed
:php:`'academic_programs'` to
:php:`ExtensionManagementUtility::addStaticFile()`, so the entry offered in a
template record as :guilabel:`Academic Partners Page Setup` pointed at
:file:`EXT:academic_programs/Configuration/TypoScript/`, the TypoScript of a
different extension. The TypoScript of this extension was registered nowhere and
could only be reached through the site set :yaml:`fgtclb/academic-partners`.

What that cost an installation without site sets: the page template directory
of this extension never entered :typoscript:`page.10.templateRootPaths`, and
the :php:`PartnershipProcessor` data processor was never registered, so
partnerships were not resolved on any page. An installation that has this
extension without :composer:`fgtclb/academic-programs` included nothing at all,
silently, because :php:`SysTemplateTreeBuilder` skips an include whose
extension is not loaded.

**The backend layout was imported by the site set only.**
:file:`Configuration/page.tsconfig` is included for the whole installation
since TYPO3 v12.0 (Feature: #96614), while a site set is opt-in per site. The
backend layout of the page type this extension registers was imported only by
:file:`Configuration/Sets/AcademicPartners/page.tsconfig`. On a site that does
not enable that set the layout :typoscript:`pagets__AcademicPartner` resolved
nowhere: the page properties showed :guilabel:`[ MISSING LABEL ]` for it and it
could not be selected for a new page at all. On TYPO3 v12 there was no delivery
path for the layout whatsoever, because site sets arrived in TYPO3 v13.1
(Feature: #103437).

The static templates of this extension are registered under its own key now,
and :file:`Configuration/page.tsconfig` imports the backend layout. The missing
label of the layout's content column was added to
:file:`Resources/Private/Language/locallang_be.xlf`. The empty
:file:`Configuration/TsConfig/page.tsconfig` was removed.

Impact
======

The static templates of this extension include its own TypoScript. The backend
layout is available on every installation, whether or not it uses site sets.

The static templates were also cut per component in this version, and the
entry :guilabel:`Academic Partners Page Setup` is now called
:guilabel:`Academic Partners: Shared plugin settings and page rendering`, see
:ref:`breaking-site-sets-and-static-templates-restructured`.

Affected Installations
======================

All installations of this extension.

Migration
=========

The static template fix is **not self-healing**. A template record that
selected the entry before stores
:file:`EXT:academic_programs/Configuration/TypoScript/`, which is still a valid
registration of the other extension, so the record keeps working and keeps
including the TypoScript of :composer:`fgtclb/academic-programs` instead of
this one.

Open the template record, tab :guilabel:`Advanced Options`, field
:guilabel:`Include TypoScript sets`, and add
:guilabel:`Academic Partners: All components (academic_partners)`, or the
component entries the site needs. Remove the selected
:guilabel:`Academic Programs: Shared plugin settings and page rendering
(academic_programs)` unless the site uses that extension on purpose.

No upgrade wizard ships for it: the stored value cannot be told apart from an
intentional selection of the programs entry, and rewriting it would break an
installation that meant the latter.

Nothing has to be done for the backend layout. Pages already carrying
:typoscript:`pagets__AcademicPartner` resolve it from now on.

..  index:: TSConfig, TypoScript, Backend, ext:academic_partners
