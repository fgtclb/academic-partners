..  _integrator-partner-list:

============
Partner List
============

..  image:: /Images/Icons/list.svg
    :width: 48px
    :alt: Icon of the Partner List content element

:CType:
    ``academicpartners_list``

:Plugin:
    `AcademicPartners`, plugin `List`, action
    :php:`PartnerController::listAction()`

:Set:
    `fgtclb/academic-partners-list`, which the aggregate set
    `fgtclb/academic-partners` includes, see :ref:`configuration-components`

The :guilabel:`Partner List` shows the partners of a site as cards: the name of
the partner linked to its page, its categories grouped by category type, and the
first image of the partner page, usually its logo. Above the cards a form lets
the visitor sort the list and filter it by category. It is the element for an
overview page of all partners, or of the partners of one region, programme or
kind of cooperation.

..  figure:: /Images/Screenshots/partner-list-default.png
    :alt: A form with selects for the sorting field, the sorting direction and the category filters, a line with the number of partners found, two partner cards with name and categories, the first with a logo and the second with a grey placeholder, and a page navigation below them.
    :class: with-border with-shadow

    The Partner List of the development instance with its sorting and filter
    form and the first page of partners. The instance enables the pagination
    and switches on the site setting
    :typoscript:`plugin.tx_academicpartners.filter.showResultCount`, which is
    off by default and shows the number of partners found.

A partner is a page of the page type :guilabel:`Academic partner`. The list
shows the partner pages that sit directly in the storage folders the editor
chooses in :confval:`Startingpoint <partner-list-pages>`, in the order of
:confval:`Default sorting <partner-list-sorting>` and narrowed down by
:confval:`Default categories <partner-list-categories>` until the visitor
selects something else.

The element is not cached: the list is built on every request, so the
selection of the visitor takes effect at once. A selection is part of the URL
and can be bookmarked or shared, see :ref:`feature-1790226100`.
A sorting the form does not offer, in the URL or in a submission, is ignored,
also a list of values where the form sends one. The default of the extension
applies in its place where the element shows the sorting selects, the preset
of the element where it hides them.

The names of the partners are headings one level below the header of the
content element, and one further level down when the content element has a
subheader. The header layout of the content element therefore decides their
level.

Related chapters:

*   :ref:`styling-list` describes the markup and :ref:`styling-item` the card
    of a partner. The extension ships no stylesheet.
*   :ref:`configuration-list-filter` and :ref:`configuration-active-filters`
    describe the site settings that decide which category filters the form
    offers, and whether it shows the active filters, a reset link and the number
    of partners found.
*   :ref:`configuration-route-enhancers` turns the filter, the sorting and the
    page into a readable path.
*   :ref:`configuration-content-element-header` is for a site whose content
    element layout renders no header.
*   The :ref:`Partner Map <integrator-partner-map>` shows the same partners on
    a map, with the same form and the same options.

..  _integrator-partner-list-configuration:

Settings
========

The tab :guilabel:`Configuration` of the content element holds the options of
the list, on two tabs of their own, :guilabel:`Filter` and
:guilabel:`Pagination`, and below them the field :guilabel:`Startingpoint`.

..  confval:: Startingpoint
    :name: partner-list-pages
    :type: page
    :Default: empty

    The pages or storage folders the partner pages sit in. The list shows the
    partner pages that are direct subpages of one of the chosen pages. Partner
    pages further down the page tree are not found, so choose every folder that
    holds partners. The form offers no :guilabel:`Recursive` field for this
    element.

    Leave it empty to list every partner page of the installation, those of
    every other site included. On an installation with more than one site, an
    empty value is rarely what is wanted.

Filter
------

..  confval:: Hide category filtering
    :name: partner-list-hide-filter
    :type: checkbox
    :Default: off

    On, the form shows no category selects, and neither the active filter tags
    nor the reset link of :ref:`configuration-active-filters`. The number of
    partners found is still shown when the site switches it on.

    Use it for a list that shows a fixed selection, together with
    :confval:`Default categories <partner-list-categories>`. With the filter
    hidden, the list always shows the partners of the default categories,
    also when the visitor changes the sorting. Categories in the URL of a link
    narrow the list further and never replace a default category, so a link
    can show fewer partners but not others. Without default categories, such a
    link filters the list as before.

..  confval:: Hide sorting
    :name: partner-list-hide-sorting
    :type: checkbox
    :Default: off

    On, the form shows neither the select of the sorting field nor the one of
    the direction, and the list keeps the order of :confval:`Default sorting
    <partner-list-sorting>`, also when the visitor filters it, and whatever
    sorting the URL of a link carries. With the category filter hidden as
    well, the element renders no form at all.

..  figure:: /Images/Screenshots/partner-list-without-form.png
    :alt: Partner cards with name, categories and logo, without any form above them.
    :class: with-border with-shadow

    A Partner List with the filter and the sorting hidden: the element renders
    the partners without a form.

..  confval:: Default sorting
    :name: partner-list-sorting
    :type: select
    :Default: Title A-Z

    The order of the list until the visitor chooses another one in the form.

    ..  list-table::
        :header-rows: 1

        *   -   Item
            -   Value
            -   Order
        *   -   :guilabel:`Title A-Z`
            -   `title asc`
            -   By the title of the partner page.
        *   -   :guilabel:`Title Z-A`
            -   `title desc`
            -   The same, reversed.
        *   -   :guilabel:`Last updated (latest first)`
            -   `lastUpdated desc`
            -   By the field :guilabel:`Last Update` of the partner page, which
                an editor fills in by hand on the tab :guilabel:`Metadata`. It
                is not the time the page was last saved.
        *   -   :guilabel:`Last updated (latest last)`
            -   `lastUpdated asc`
            -   The same, reversed.
        *   -   :guilabel:`Page sorting (result may be unexpected)`
            -   `sorting asc`
            -   By the position of the partner page in the page tree. The
                position is only meaningful among the pages of one folder, so
                the partners of several folders interleave.

    Partners equal in the chosen order follow the order of their uid.

    The visitor sorts with two selects, :guilabel:`Sorting field` and
    :guilabel:`Sorting direction`. They also offer page sorting in descending
    order, which this option does not.

    A content element saved before the option existed sorts by title, A to Z.
    Only the items of the select are supported. A value that is not a field and
    a direction, an empty one or a field alone for example, which the form never
    stores but an import or a TypoScript value for a content element without
    the field can produce, is ignored, and the list sorts by title, A to Z. Of
    a field and a direction, an unknown one is ignored on its own and the other
    one still applies, so `uid desc` sorts by title, Z to A.

..  confval:: Default categories
    :name: partner-list-categories
    :type: records
    :Default: empty

    Categories the list is filtered by until the visitor selects something in
    the form. The tree offers the categories of the category types of the group
    `partners` in the default language.

    The list shows a partner only when its page carries **every** chosen
    category, also where two of them are of the same category type. The
    subcategories of a chosen category are not added. The description below
    the field in the backend says otherwise, it was taken over from
    :guilabel:`academic_programs` and does not describe this list.

    The form shows the chosen categories as selected. A visitor who submits
    the form replaces the chosen categories of the category types the form
    offers with their own selection, and the reset link of
    :ref:`configuration-active-filters` brings them back. A chosen category of
    a type the form does not offer, because :ref:`configuration-list-filter`
    leaves it out, stays. To keep a list to a
    fixed selection, hide the filter, see
    :confval:`Hide category filtering <partner-list-hide-filter>`.

..  confval:: Show hidden records
    :name: partner-list-show-hidden-records
    :type: checkbox
    :Default: off

    On, the list includes partner pages that are hidden, for every visitor and
    whatever the preview settings of the backend are. Start and stop times,
    access restrictions and deleted pages still apply. The partner page itself
    stays hidden, so the visitor sees the partner in the list and cannot open
    its page.

    Use it for a partner that has no page of its own to show yet, but belongs
    in the list.

..  _configuration-list-pagination:

Pagination
----------

The tab :guilabel:`Pagination` splits the partners into pages, with a
navigation between them below the list.

..  confval:: Enable pagination
    :name: partner-list-pagination-enabled
    :type: checkbox
    :Default: off

    Off, the list renders every partner the filter matches. On, it renders one
    page of them and a navigation to the others. A list that fits on one page
    renders no navigation.

..  confval:: Results per page
    :name: partner-list-results-per-page
    :type: number
    :Default: ``10``

    The number of partners on one page. Only read while :confval:`Enable
    pagination <partner-list-pagination-enabled>` is on. A value below 1 is read
    as 10.

..  figure:: /Images/Screenshots/partner-list-pagination.png
    :alt: A page navigation with the links first, previous, next and last and the page numbers, the current page 2 marked.
    :class: with-border with-shadow

    The navigation of a paginated Partner List.

How many page numbers the navigation links at once is one value for the whole
site, see :ref:`configuration-pagination-number-of-links`.

Every page link keeps the active filter and sorting, and a filter submission
starts on page one. A page beyond the last one shows the last page. The
category options of the filter still come from every partner the list found,
not from the page shown.

The :guilabel:`Partner Map` is never paginated.

..  _integrator-partner-list-two-lists:

Two lists on one page
=====================

All :guilabel:`Partner List` elements of a page read the selection from the same
URL arguments. A submission of the form is answered by the first of them on
the page: it redirects to a URL that carries the selection as that element
sees it, the presets it keeps included. For another :guilabel:`Partner List`
element of the page, a sorting the first element hides replaces the one
its visitor chose. The default categories of a category filter the first
element hides, and its default categories of the category types its form does
not offer, are added to the categories the visitor chose. Put lists whose
forms hide or offer different parts on pages of their own.
