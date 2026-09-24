.. _feature-1790272109:

==========================================
Feature: The partner list can be paginated
==========================================

Description
===========

The :guilabel:`Partners List` content element rendered every matching partner
on one page. It now has a :guilabel:`Pagination` tab with two fields:

*   :guilabel:`Enable pagination`, off by default. Off, the list renders every
    partner, as before.
*   :guilabel:`Results per page`, 10 by default.

With the pagination enabled, the list shows one page of partners and a
navigation to the other pages below it. When
`georgringer/numbered-pagination` is installed, the navigation links at most
:typoscript:`plugin.tx_academicpartners.pagination.numberOfLinks` page numbers
around the current one, 5 by default, and marks the pages it leaves out with an
ellipsis. Without it, the core pagination links every page. The number is a
site setting of the set `fgtclb/academic-partners-list` and a constant of the
static template, see :ref:`configuration-list-pagination`.

*   Every page link carries the active filter and sorting, in the URL shape of
    :ref:`feature-1790226100`, and the page as
    :php:`tx_academicpartners_list[demand][currentPage]`. On the page without
    any list argument, that is the categories and the sorting the editor
    preselected.
*   A filter or sorting submission starts on page one of the new selection.
*   A page beyond the last one shows the last page, and a page below one the
    first.
*   The category options of the filter still come from every partner the list
    found, not from the page shown.
*   A list that fits on one page renders no navigation.

The :guilabel:`Partners Map` is not paginated: it keeps drawing every partner
the filter matches. It used to share the FlexForm data structure of the list;
it now has one of its own, :file:`Configuration/FlexForms/MapSettings.xml`,
with the same fields as before. The field and sheet names of both content
elements are unchanged, so stored values and page TSconfig that addresses them
keep working.

Impact
======

*   Nothing changes until an editor enables the pagination on a content
    element.
*   The partial :file:`Partner/ItemList.html` renders one page of partners and
    the new partial :file:`Partner/Pagination.html`. The variables
    :html:`{paginator}`, :html:`{pagination}` and :html:`{demandArguments}` are
    assigned only while the pagination is enabled; :html:`{partners}` is still
    every partner the list found. A project that overrides
    :file:`Partner/ItemList.html` renders every partner and no navigation, even
    with the pagination enabled, until it takes over the change. So does a
    project whose override of :file:`Templates/Partner/List.html` renders the
    item list with arguments of its own instead of :html:`{_all}`: it has to
    pass the three variables on.
*   A project that paginates the list itself - a subclass of
    :php:`PartnerController` that overrides :php:`listAction()`, a FlexForm
    field added through an event listener, pagination partials of its own -
    should remove all of it with this update. Editors would otherwise see two
    pagination switches, and the values stored in the project's own field are
    not taken over: enable the pagination again on each content element that
    used it.

.. index:: Backend, Frontend, FlexForm, NotScanned
