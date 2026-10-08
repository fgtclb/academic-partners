..  _important-translated-partnerships-keep-their-role:

==================================================
Important: Translated partnerships keep their role
==================================================

Description
===========

A translated partnership keeps the role of its default language record, the
:guilabel:`Role` field of the partnership is not translated. The list of
partnerships on a role contradicted that: it was synchronized into every
translation of the role. Saving a role, in its default language or as a
translation and even without a change, wrote the uid of the role translation
into the role of every translated partnership of that role and renumbered
their position in its list.

The list of partnerships is now kept on the default language role only. It is
not offered on a translation of a role any more, where it was not shown before
either, and saving a role leaves the translated partnerships alone.

Impact
======

Two kinds of records can be left from before the update, and both point to a
translation of their role. This query lists them:

..  code-block:: sql

    SELECT partnership.uid, partnership.l10n_parent, partnership.l10n_source, partnership.role
    FROM tx_academicpartners_domain_model_partnership AS partnership
    JOIN tx_academicpartners_domain_model_role AS role ON role.uid = partnership.role
    WHERE partnership.deleted = 0 AND role.sys_language_uid > 0;

*   A translated partnership whose role was rewritten. Saving its default
    language record, its :sql:`l10n_parent`, copies the role onto the translation again
    and appends the translation to the list of that role.
*   A duplicate created by localizing a role whose partnerships were
    translated already: a second translation for the same :sql:`l10n_parent`
    and language, copied from the translation in that language, so its
    :sql:`l10n_source` points to a translation of the same language rather
    than to the default language record. Delete it, saving repairs nothing
    here. A translation made from a translation in another language is not a
    duplicate and is not listed. This query lists the duplicates:

    ..  code-block:: sql

        SELECT duplicate.uid, duplicate.l10n_parent, duplicate.l10n_source
        FROM tx_academicpartners_domain_model_partnership AS duplicate
        JOIN tx_academicpartners_domain_model_partnership AS source ON source.uid = duplicate.l10n_source
        WHERE duplicate.deleted = 0 AND source.sys_language_uid > 0
            AND source.sys_language_uid = duplicate.sys_language_uid;

    The duplicates point to the translation of the role as well, so the first
    query lists them too. Delete them before saving the default language
    records that repair the others.

Localizing a role still localizes its partnerships, because core localizes
every inline child of a localized record, and no configuration prevents that.
Partnerships without a translation get one that points to the translation of
the role, which the first query lists. Partnerships that are translated already
are copied once more, and the copies are no longer removed again, the second
query lists them. Run both queries after localizing a role.

Affected Installations
======================

Installations that translate partnerships and partner roles.

..  index:: Backend, TCA, ext:academic_partners
