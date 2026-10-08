..  _important-1791547201:

=========================================================
Important: Translations of partnerships keep their parent
=========================================================

Description
===========

The table `tx_academicpartners_domain_model_partnership` now declares the
columns `sys_language_uid`, `l10n_parent`, `l10n_source` and `l10n_diffsource`
in its TCA, the way the role table of the extension always did.

It declared none of them before. TYPO3 v13 creates a missing `l10n_parent`
column as a select of the table itself, TYPO3 v12 adds it, and `l10n_source`,
as `passthrough`. On TYPO3 v12 a translation written in the same DataHandler
run as its original, with the original named by its `NEW` placeholder, was then
stored with the value of an unrelated remap stack entry: the uid of some other
record of that run. Imports and generated content create translations that way,
the backend localization does not, because it names the original by its uid.

Impact
======

On TYPO3 v12 a translation created together with its original points at that
original. `l10n_source` stays a `passthrough` column, as on TYPO3 v13, and is
right because a data map names it with the same placeholder directly after
`l10n_parent`, so it takes over the value resolved for that select.

The backend form of a translated partnership now renders the field
"Translation original", which its type has always listed.

`sys_language_uid` is declared as an exclude field, like on the role table and
like TYPO3 v13 creates it. The column TYPO3 v12 added on its own was no exclude
field, so on TYPO3 v12 a backend user group needs
`tx_academicpartners_domain_model_partnership:sys_language_uid` in its allowed
excludefields to see and set the language of a partnership from now on.

The database schema does not change: TYPO3 derives the four columns from the
`ctrl` section of the table, which names them unchanged.

Affected Installations
======================

TYPO3 v12 installations that created translated partnerships with a data map
holding both the original and the translation, for example an import. Such
translations may point at a wrong or a missing record, show the default
language content in the frontend, and fail to open in the backend with a
`DatabaseDefaultLanguageException`.

TYPO3 v12 installations whose editors edit partnerships without the language
field among the allowed excludefields of their groups.

TYPO3 v13 installations are not affected.

Migration
=========

Add `tx_academicpartners_domain_model_partnership:sys_language_uid` to the
allowed excludefields of every backend user group whose members set the
language of a partnership.

The wrong translation parents cannot be repaired automatically, the original a
translation belongs to is not stored anywhere else. Find the translations whose
parent is not a default language record of the same table on the same page:

..  code-block:: sql

    SELECT t.uid, t.pid, t.l10n_parent
    FROM tx_academicpartners_domain_model_partnership t
    LEFT JOIN tx_academicpartners_domain_model_partnership o
        ON o.uid = t.l10n_parent
        AND o.sys_language_uid IN (0, -1)
        AND o.pid = t.pid
        AND o.deleted = 0
    WHERE t.sys_language_uid > 0
        AND t.l10n_parent > 0
        AND t.deleted = 0
        AND o.uid IS NULL;

`t.l10n_parent > 0` leaves out translations in free mode, which have no
original by design.

Set `l10n_parent` and `l10n_source` of each row to the uid of its original, or
delete the row and localize the original again in the backend.

.. index:: TCA, ext:academic_partners
