..  _important-ace-430-academic-partners:

=====================================================
Important: Each partnership renders once per language
=====================================================

Description
===========

The partnerships of a page render each partnership once in every language,
on TYPO3 v12 and v13 alike: in the partnerships list, in the partnerships
teaser and in the :php:`partnerships` the page data processor hands to the
template of a partner page. A partnership renders as its translation where it
has one, and as its default language record where it has none. A partnership
that exists in one language only renders in that language, and one for all
languages renders in every language.

Before, a translation attached to the default language partner page rendered
next to its default record, in the default language and in the translated
one. Localizing a partner role created such translations up to version 2.3,
and localizing a partnership on its own in the :guilabel:`List` module still
does. They render once now, as the partnership in their language.

A partnership that renders as its translation names its role in that
language, where the role is translated. An untranslated partnership names its
role, and its partner, the way the default language does.

The partnerships list and teaser group the partnerships by role. A page with
translated and untranslated partnerships of one role lost some of them in a
translated language, because the template compared two different role
objects. The templates now compare the uid of the role, and the role heading
is named in the visitor's language when one partnership of the group carries
the translated role. A site package that overrides
:file:`Partner/PartnershipsList.html` or :file:`Partner/PartnershipsTeaser.html`
changes the same condition:

..  code-block:: html

    <f:if condition="{partnership.role.uid} == {role.uid}">

A frontend preview that shows hidden records keeps showing hidden
partnerships.

Impact
======

Nothing has to be cleaned up. The translations attached to the default
language partner page stay in the database and stop rendering twice. This
query lists them, the copies a role localization created and the partnerships
localized on their own in the :guilabel:`List` module alike, so each can be
judged and deleted where it is a copy:

..  code-block:: sql

    SELECT partnership.uid, partnership.l10n_parent, partnership.sys_language_uid
    FROM tx_academicpartners_domain_model_partnership AS partnership
    JOIN pages AS page ON page.uid = partnership.page
    WHERE partnership.deleted = 0 AND partnership.t3ver_wsid = 0
        AND partnership.l10n_parent > 0 AND page.sys_language_uid = 0;

:ref:`important-translated-partnerships-keep-their-role` explains how the
copies came about and how to repair the role of the translations.

A partnership can name its role in the default language although it renders
as its translation. Once the data mapper of TYPO3 v12 and v13 has loaded the
default language role for an untranslated partnership, it hands that role to
every following partnership of the page with the same role.

Affected Installations
======================

Installations with translated partnerships, and site packages that override
the templates of the partnerships list or teaser.

..  index:: Frontend, Fluid, ext:academic_partners
