.. _important-partner-sorting-submitted-as-a-list-is-ignored:

===================================================
Important: A sorting submitted as a list is ignored
===================================================

Description
===========

The partner list and the partner map read the sorting of a request as one
value per argument: the sorting field, the sorting direction and the combined
sorting. A submission or a link whose demand carries a list where the form
sends one value, such as
`tx_academicpartners_list[demand][sortingField][]=title`, stopped the content
element with a :php:`TypeError`.

Such a list is now ignored like an unknown value, so the element sorts by
title, A to Z, the default of the extension, unless the request carries another
sorting value that applies. A list given as the :guilabel:`Default sorting` of
the element is ignored already, see
:ref:`important-incomplete-partner-sorting-keeps-the-default`.

Impact
======

*   A request that failed with a :php:`TypeError` of
    :php:`PartnerDemand::setSorting()`, :php:`setSortingField()` or
    :php:`setSortingDirection()` renders the partners again.
*   A sorting of the form is applied as before.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, ext:academic_partners
