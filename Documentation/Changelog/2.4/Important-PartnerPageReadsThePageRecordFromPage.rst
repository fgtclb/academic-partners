.. _important-partner-page-reads-the-page-record-from-page:

=======================================================
Important: Partner page reads the page record from page
=======================================================

Description
===========

The data processor :typoscript:`partner-data` builds the partner of a partner
page from the page record. It took that record from the variable
:typoscript:`data` first, the one a :typoscript:`FLUIDTEMPLATE` page object
assigns, and from the page information object :html:`{page}` of a
:typoscript:`PAGEVIEW` page object only when :typoscript:`data` was empty.

:typoscript:`PAGEVIEW`, which TYPO3 v13 offers, reserves :html:`{page}` but not
:typoscript:`data`, so a :typoscript:`PAGEVIEW` site package may assign a
:typoscript:`data` of its own. A text there failed every partner page with a
type error, and the records of a query were read as if they were the page
record.

The processor now takes the record from :html:`{page}` when it is the page
information object, and from :typoscript:`data` otherwise. A value that is not
a non-empty array adds no partner.

Impact
======

*   A :typoscript:`PAGEVIEW` site package that assigns a variable
    :typoscript:`data` of its own, a text or the records of a query for
    example, no longer breaks partner pages on TYPO3 v13.
*   A :typoscript:`PAGEVIEW` site package that assigned another page record as
    :typoscript:`data` on purpose now gets the partner of the page it renders.
*   A :typoscript:`FLUIDTEMPLATE` page object on TYPO3 v12 or v13 is not
    affected, also when its site package assigns a variable :html:`{page}` of
    its own.

Affected Installations
======================

Installations on TYPO3 v13 with a :typoscript:`PAGEVIEW` page object that
assigns a variable :typoscript:`data` on partner pages.

.. index:: TypoScript, Frontend, ext:academic_partners
