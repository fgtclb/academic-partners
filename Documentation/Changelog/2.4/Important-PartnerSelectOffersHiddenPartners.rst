..  _important-ace-672-academic-partners:

====================================================
Important: The partner select offers hidden partners
====================================================

Description
===========

The :guilabel:`Partner` field of a partnership record offers every partner
page that is not deleted, on TYPO3 v12 and v13 alike. Hidden partner pages are
offered, and so are partner pages whose start time lies in the future or whose
end time has passed. The record selects of TYPO3 itself do not filter by
visibility either. An editor can prepare a partnership before the partner page
goes live, and a partnership keeps its partner when the page is hidden or
expires.

On TYPO3 v13 the field offered these partners before, because Extbase ignores
the visibility of records in a query made during a backend request. TYPO3 v12
does so only when the deprecated Extbase feature toggle
``ignoreAllEnableFieldsInBe`` is enabled, so the field offered visible
partners only. A partnership whose partner page was hidden, started in the
future or had expired opened with no partner chosen, the select listed an
entry such as ``[ MISSING LABEL ("15") ]`` without choosing it, and saving the
record without touching the field removed the partner. The field now states
which partners it offers itself, so it no longer depends on the core version.

The select does not mark a hidden or timed partner yet. An editor tells them
apart from the visible ones by the partner page only.

Impact
======

Nothing changes for an editor on TYPO3 v13.

On TYPO3 v12, the select offers hidden and timed partners as well, and a
partnership keeps such a partner when it is saved. A partner that an earlier
save already removed is not restored. Choose it again in the record.

In the frontend, the partnerships list and the partnerships teaser cannot show
a partner whose page is not visible. Such a partnership still takes its place
in the list, as an empty item: a grid column holding a card without a title or
a link. This release does not change that.

Affected Installations
======================

Installations on TYPO3 v12 with partnerships whose partner page is hidden, has
a start time in the future or an end time in the past.

..  index:: Backend, TCA, ext:academic_partners
