..  _integrator-partnerships-teaser:

===================
Partner Logo Teaser
===================

..  image:: /Images/Icons/partnerships-teaser.svg
    :width: 48px
    :alt: Icon of the Partner Logo Teaser content element

:CType:
    ``academicpartners_partnershipsteaser``

:Plugin:
    `AcademicPartners`, plugin `PartnershipsTeaser`, action
    :php:`PartnerController::partnershipsTeaserAction()`

:Set:
    `fgtclb/academic-partners-partnerships-teaser`, which the aggregate set
    `fgtclb/academic-partners` includes, see :ref:`configuration-components`

The :guilabel:`Partner Logo Teaser` shows the same partners as
:ref:`Partners Linked <integrator-partnerships-list>`: the partnerships of the
page it is stored on, grouped by role, in the order the editor arranged them, and
the partners without a role after the groups. It is meant for a strip of partner
logos on the page of a project or a faculty, for example in a sidebar or above
the footer, while the full list of the partnerships is shown elsewhere. There
is no selection in the content element.

What it renders
===============

The output is the one of :guilabel:`Partners Linked`, card for card: the name
of the partner linked to its page, its categories and the first image of the
partner page. Only the outermost element differs, it carries the class
`academic-partnerships-teaser` instead of `academic-partnerships-list`. That
the teaser shows logos, and only logos, is up to the stylesheet of the site,
which for example hides the name and the categories inside
`academic-partnerships-teaser`. See :ref:`styling-partnerships` and
:ref:`styling-outermost-element`.

A site that wants different markup for the teaser, only the image of each
partner for example, overrides the partial of the teaser,
:file:`Partials/Partnerships/Teaser/Item.html`. It renders one card of the
teaser and nothing else, so :guilabel:`Partners Linked`, which renders
:file:`Partials/Partnerships/List/Item.html`, stays as it is.

..  figure:: /Images/Screenshots/partnerships-teaser-default.png
    :alt: Two role headings, each followed by a partner card with name and categories.
    :class: with-border with-shadow

    The Partner Logo Teaser as it renders without a stylesheet of its own: the
    same cards as Partners Linked.

Everything said about the partnerships on the page of
:ref:`Partners Linked <integrator-partnerships-list>` applies here as well: where
the partnerships come from, the grouping by role, and the partners without a
role, or with a hidden or deleted role, after the role groups.

..  _integrator-partnerships-teaser-settings:

Settings
========

The content element has no options of its own: it has no FlexForm and no
:guilabel:`Configuration` tab. What it shows is decided by the partnerships of
its page.
