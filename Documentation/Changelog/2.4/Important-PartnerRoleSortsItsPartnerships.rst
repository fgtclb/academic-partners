..  _important-partner-role-sorts-its-partnerships:

===========================================================
Important: A partner role sorts its partnerships on its own
===========================================================

Description
===========

A partnership is an inline child of two records at once: of its partner page,
and of the role it is filled with. Both relations wrote the same :sql:`sorting`
column, because the role relation declared no :php:`foreign_sortby` of its own
and TYPO3 then falls back to the manual sort field of the table.

:php:`RelationHandler::writeForeignField()` numbers the children of the record
being saved 1..n in the order of its form, so saving a role renumbered its
partnerships *across every partner page that owns one of them*. An editor who
had arranged the partnerships on a partner page saw that arrangement replaced
by the order of an unrelated role form. That showed in the backend and, since
partnership lists and teasers render in :sql:`sorting` order, in the frontend
as well.

The role relation now has a sort column of its own,
:sql:`tx_academicpartners_domain_model_partnership.role_sorting`, and orders by
it. The partner page keeps :sql:`sorting`, so nothing that renders today
changes.

Impact
======

Saving a role no longer changes the order of any partner page, and a partner
page no longer changes the order a role form shows. Both arrangements are kept
side by side.

A partnership that joins a role afterwards is appended to the end of that
role's list, whether it was created in the role form, given its role in the
partnership form on a partner page, copied or localized. Changing the role
moves it to the end of the new one. Clearing the role removes it from the list
altogether.

Every partnership that existed before carries :sql:`0` in the new column. The
upgrade wizard :guilabel:`Seed the partnership sort order of academic partner
roles` fills it with the order the role forms show today: the current
:sql:`sorting` of each partnership, with :sql:`uid` settling ties. Until it has
run, a partnership that joins a role whose list is still unseeded keeps
:sql:`0` as well and is left to the wizard, which is the one that knows the
order of that list.

Running the wizard again is harmless. It appends what has no position yet and
never renumbers what has one, so an arrangement made in a role form is not
reset by it.

Affected Installations
======================

Every installation of this extension that fills partnerships with roles.

Migration
=========

Update the database schema to add the new column, in
:guilabel:`Admin Tools > Maintenance > Analyze Database Structure` or on the
command line, then run the upgrade wizard once:

..  code-block:: bash

    vendor/bin/typo3 extension:setup --extension=academic_partners
    vendor/bin/typo3 upgrade:run academicPartners_seedPartnershipRoleSorting

..  index:: Backend, Database, TCA, ext:academic_partners
