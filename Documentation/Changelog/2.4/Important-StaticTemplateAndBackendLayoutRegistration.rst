.. _important-1786968600:

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
template record as *Academic Partners Page Setup* pointed at
:file:`EXT:academic_programs/Configuration/TypoScript/` — the TypoScript of a
different extension. The TypoScript of this extension was registered nowhere and
could only be reached through the site set :yaml:`fgtclb/academic-partners`.

What that costs an installation without site sets: the page template directory
of this extension never enters :typoscript:`page.10.templateRootPaths`, and the
:php:`PartnershipProcessor` data processor is never registered, so partnerships
are not resolved on any page. An installation that has this extension without
:composer:`fgtclb/academic-programs` included nothing at all, silently —
:php:`SysTemplateTreeBuilder` skips an include whose extension is not loaded.

**The backend layout was imported by the site set only.**
:file:`Configuration/page.tsconfig` is auto-included for the whole installation
since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per site. The backend
layout of the page type this extension registers, and the descriptions of its
content elements, were imported only by
:file:`Configuration/Sets/AcademicPartners/page.tsconfig`. On a site that does
not enable that set the layout :typoscript:`pagets__AcademicPartner` resolved
nowhere: the page properties showed :guilabel:`[ MISSING LABEL ]` for it and it
could not be selected for a new page at all.

On TYPO3 v12 the loss is total rather than partial: site sets do not exist on
that version at all (they arrived in v13.1, Feature: #103437), so there was no
delivery path for the layout whatsoever.

Both imports moved to :file:`Configuration/page.tsconfig`, where
:composer:`fgtclb/academic-programs` already had them, and the copy in the site
set was removed rather than left to be applied twice. The empty
:file:`Configuration/TsConfig/page.tsconfig`, which nothing imports any more,
was removed with it.

The missing label of the layout's content column was added to
:file:`Resources/Private/Language/locallang_be.xlf` in the same change.

Impact
======

The static template *Academic Partners Page Setup* now includes the TypoScript
of this extension. The backend layout and the content element descriptions are
available on every installation, whether or not it uses site sets.

Affected Installations
======================

All installations of this extension.

The static template fix is **not self-healing**. A template record that selected
the entry before stores
:typoscript:`EXT:academic_programs/Configuration/TypoScript/`, which is still a
valid registration of the other extension, so the record keeps working and
keeps including the wrong tree. The now correct entry has to be added to the
record by hand. No upgrade wizard ships for it: the stored value is genuinely
ambiguous — it cannot be told apart from an intentional selection of
*Academic Programs Page Setup* — and rewriting it would break an installation
that meant the latter.

Nothing has to be done for the backend layout. Pages already carrying
:typoscript:`pagets__AcademicPartner` resolve it from now on.

.. index:: TSConfig, TypoScript, Backend, ext:academic_partners
