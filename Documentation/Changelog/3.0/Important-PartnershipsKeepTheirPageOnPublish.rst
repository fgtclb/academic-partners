.. _important-partnerships-keep-their-page-on-publish:

=========================================================================
Important: Partnerships keep their page and role when either is published
=========================================================================

Description
===========

A partnership points at its page and at its partner role. Both pointers are
declared as relations, so TYPO3 treated them like any other relation when a
page or a role was edited in a workspace: the first edit versions the
partnerships together with the page or role, and TYPO3 pointed each
partnership version at the version row of the page or role, which publishing
deletes.

Published from the workspace module, TYPO3 writes the live page or role into
the partnerships once more after publishing, and nothing was lost, except in
two cases:

*   The workspace was published from the live workspace: by the scheduler
    through the publish date of the workspace (:bash:`workspace:autopublish`),
    or by other code that publishes as a user of the live workspace.
*   The page and the partner role were both edited in the same workspace. The
    partnerships then lost their page, from the workspace module as well.

In both cases the partnership belonged to no page, or had a role that no
longer existed. The partnerships plugins and the page showed nothing, and a
later edit of the page no longer included the partnership.

Both pointers now name their table in :php:`dontRemapTablesOnCopy`. A
partnership keeps its live page and role through a publish from either place,
and a copied page or role still gets copies of its partnerships that point at
the copy.

Impact
======

Neither the database schema nor an upgrade wizard is involved.

Partnerships that lost their page or role this way are not repaired. TYPO3
logs the uid of a published version only in :sql:`sys_log`, and on TYPO3 v13
and v14 in :sql:`sys_history` as well, and both are routinely cleaned, so the
original page or role cannot be told reliably. These queries list the live
partnerships whose page or role no longer exists:

..  code-block:: sql

    SELECT pa.uid, pa.pid, pa.page
      FROM tx_academicpartners_domain_model_partnership AS pa
      LEFT JOIN pages AS p ON p.uid = pa.page
     WHERE pa.deleted = 0 AND pa.t3ver_wsid = 0
       AND pa.page > 0 AND p.uid IS NULL;

    SELECT pa.uid, pa.pid, pa.role
      FROM tx_academicpartners_domain_model_partnership AS pa
      LEFT JOIN tx_academicpartners_domain_model_role AS r ON r.uid = pa.role
     WHERE pa.deleted = 0 AND pa.t3ver_wsid = 0
       AND pa.role > 0 AND r.uid IS NULL;

Open each of them in the list module, :guilabel:`Web > List` on TYPO3 v13
and :guilabel:`Content > Records` on TYPO3 v14, on the page given as
:sql:`pid`, and select its page or role again, or delete it and add the
partnership on its page anew.

Affected Installations
======================

Installations that publish workspaces through their publish date, or that edit
a page and a partner role in the same workspace. Every release of version 2
has the same defect and can hold such partnerships, so check an installation
that is upgraded from version 2.

.. index:: Backend, TCA, ext:academic_partners
