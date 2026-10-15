..  _integrator-partnerships-list:

===============
Partners Linked
===============

..  image:: /Images/Icons/partners.svg
    :width: 48px
    :alt: Icon of the Partners Linked content element

:CType:
    ``academicpartners_partnershipslist``

:Plugin:
    `AcademicPartners`, plugin `PartnershipsList`, action
    :php:`PartnerController::partnershipsListAction()`

:Set:
    `fgtclb/academic-partners-partnerships-list`, which the aggregate set
    `fgtclb/academic-partners` includes, see :ref:`configuration-components`

:guilabel:`Partners Linked` shows the partners an editor linked to a page: the
partners of a project, of a faculty or of a study programme, each with the
role the partner plays there. It is the element for the page the partnerships
belong to, not for an overview of all partners, which is the
:ref:`Partner List <integrator-partner-list>`.

Where the partners come from
============================

Every page except folders and the recycler has a tab :guilabel:`Partnership`
with the field :guilabel:`Partnerships`. Each partnership names a
:guilabel:`Partner`, a page of the page type :guilabel:`Academic partner`, and
optionally a :guilabel:`Role`, a record kept in a storage folder. The editor
orders the partnerships by dragging them in the page form.

The content element shows the partnerships of the page it is stored on, in that
order. Put it on the page that carries the partnerships. A content element
shown on another page, through a :guilabel:`Insert records` element for
example, still shows the partnerships of its own page. A hidden partnership is
not shown.

Each partnership renders once in the language of the page, as its translation
where it has one, see :ref:`important-ace-430-academic-partners`.

What it renders
===============

A partner is rendered as in the :ref:`Partner List <integrator-partner-list>`:
the name linked to the partner page, the categories and the first image of the
partner page.

*   While at least one partnership of the page has a role, the element renders
    one group per role, in the order in which the roles first occur among the
    partnerships. The name of the role is the heading of its group, and the
    partners of the role follow it.
*   While no partnership has a role, the element renders the partners in one
    list without a heading.

..  figure:: /Images/Screenshots/partnerships-list-default.png
    :alt: Two role headings, the first followed by two partner cards and the second by one, each card with name, categories and logo.
    :class: with-border with-shadow

    Partners Linked groups the partners of the page by the role of the
    partnership.

..  warning::

    A partnership without a role is not rendered as soon as another
    partnership of the page has a role. On a page that uses roles, give every
    partnership one.

The role headings take their level from the header layout of the content
element, the names of the partners are one level further down.

:ref:`styling-partnerships` describes the markup. The element is cached with the
page. Saving the partnerships in the form of their page clears the cache of that
page.

..  _integrator-partnerships-list-settings:

Settings
========

The content element has no options of its own: it has no FlexForm and no
:guilabel:`Configuration` tab. What it shows is decided by the partnerships of
its page. The header switch of :ref:`configuration-content-element-header`
applies to it as to every content element of this extension.
