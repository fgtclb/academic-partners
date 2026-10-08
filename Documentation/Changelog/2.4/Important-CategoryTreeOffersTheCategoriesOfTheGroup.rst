..  _important-category-tree-offers-the-categories-of-the-group:

===============================================================
Important: The category tree offers the categories of the group
===============================================================

Description
===========

The :guilabel:`Default categories` field of the partner list and partner map
plugins offered every category that had a type, including the categories of
other extensions, such as degrees or job types. The plugins only ever use the
categories of the :php:`partners` group and silently ignored any other
selection.

The tree now offers the categories that carry a type of the :php:`partners`
group, and the categories above them, so a category of the group below a parent
of another type or of none is still reachable. Such a parent can be selected
and is ignored, as before. Types an integrator adds to the group are offered
as well.

Impact
======

Categories of other groups are no longer offered in the tree. A selection of
such a category that is stored already is not removed by the update, and the
frontend keeps ignoring it.

..  index:: Backend, FlexForm, ext:academic_partners
