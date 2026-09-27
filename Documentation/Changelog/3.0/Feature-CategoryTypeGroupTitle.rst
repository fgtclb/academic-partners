..  _feature-1790592003:

===================================================
Feature: The partners group has a title and an icon
===================================================

Description
===========

The category types of the extension are grouped under the key
:yaml:`partners`, and the type select of a category headed them with that key.
The extension now declares the group in its
:file:`Configuration/CategoryTypes.yaml`, with the title
:guilabel:`Academic Partners` (German: :guilabel:`Akademische Partner`) and
an icon.

Impact
======

The type select of a category heads the partner types with the title. The
icon is registered as :php:`category_types.group.partners`, from
:file:`Resources/Public/Icons/CategoryGroups/Partners.svg`, a Font Awesome
Free icon listed in :file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

A site package can change the title or the icon by declaring the group
:yaml:`partners` again, see the developer documentation of
:php:`EXT:category_types`, section :guilabel:`Naming a group`.

.. index:: Backend, ext:academic_partners
