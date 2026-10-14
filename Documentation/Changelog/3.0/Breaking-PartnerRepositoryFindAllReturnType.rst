..  _breaking-partner-repository-find-all-return-type:

=========================================================
Breaking: The partner repository declares what it returns
=========================================================

Description
===========

:php:`\FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository::findAll()`
declares :php:`\TYPO3\CMS\Extbase\Persistence\QueryResultInterface` as its
return type, the type of the Extbase repository method it overrides. It
declared :php:`?\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult` before,
although it never returned :php:`null`: it returns the result of its query,
which is an empty result when there is no partner page.

The partner select of a partnership record no longer checks the result for
:php:`null`. It offers the same partners as before, on TYPO3 v13 and v14.

Impact
======

A class that extends :php:`PartnerRepository` and overrides :php:`findAll()`
with the return type :php:`?QueryResult` stops loading with a fatal error,
because its declaration is no longer compatible with the method it overrides.

Code that calls :php:`findAll()` and compares the result with :php:`null`
keeps working, the result is just never :php:`null`.

Affected Installations
======================

Installations with a class that extends :php:`PartnerRepository` and declares
its own :php:`findAll()`, an XCLASS of the repository included.

Migration
=========

Declare :php:`QueryResultInterface` as the return type of the overriding
method, or a narrower type the method really returns, and drop the
:php:`null` checks on its result.

..  index:: PHP-API, ext:academic_partners
