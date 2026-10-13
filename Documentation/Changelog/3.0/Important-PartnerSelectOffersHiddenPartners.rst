..  _important-ace-672-academic-partners:

====================================================
Important: The partner select offers hidden partners
====================================================

Description
===========

The :guilabel:`Partner` field of a partnership record offers every partner
page that is not deleted. Hidden partner pages are offered, and so are partner
pages whose start time lies in the future or whose end time has passed. The
record selects of TYPO3 itself do not filter by visibility either. An editor
can prepare a partnership before the partner page goes live, and a partnership
keeps its partner when the page is hidden or expires.

On TYPO3 v13 and v14 the field offered these partners before, because Extbase
ignores the visibility of records in a query made during a backend request.
The field now states it itself, so it no longer depends on that default.

The select does not mark a hidden or timed partner yet. An editor tells them
apart from the visible ones by the partner page only.

Impact
======

Nothing changes for an editor on TYPO3 v13 or v14.

On TYPO3 v12, version 2.3 and older offered visible partners only, unless the
deprecated Extbase feature toggle ``ignoreAllEnableFieldsInBe`` was enabled.
Installations upgrading from there see hidden and timed partners in the select
for the first time.

In the frontend, the partnerships list and the partnerships teaser cannot show
a partner whose page is not visible. Such a partnership still takes its place
in the list, as an empty item: a grid column holding an
:html:`<article class="ace-item">` without a title, a link or a logo. This
release does not change that.

Affected Installations
======================

Installations with partnerships whose partner page is hidden, has a start time
in the future or an end time in the past.

..  index:: Backend, TCA, ext:academic_partners
