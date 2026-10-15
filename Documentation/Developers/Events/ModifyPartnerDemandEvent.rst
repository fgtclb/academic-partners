..  _developers-event-modify-partner-demand-event:

========================
ModifyPartnerDemandEvent
========================

:php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent` hands a listener
the demand of the :guilabel:`Partner List` and the :guilabel:`Partner Map`
before the partners are queried. The demand is the query: what a listener sets
on it decides which partners are listed or drawn, and in which order.

When it is dispatched
=====================

In :php:`PartnerController::listAction()` and
:php:`PartnerController::mapAction()`, on every request that renders one of the
two content elements, after the demand is built and before the partners are
queried. The demand then holds:

*   the storage pages of :confval:`Startingpoint <partner-list-pages>`,
*   the choice of
    :confval:`Show hidden records <partner-list-show-hidden-records>`,
*   the sorting and the categories the visitor selected, or, on a request
    without a selection of the visitor, those of
    :confval:`Default sorting <partner-list-sorting>` and
    :confval:`Default categories <partner-list-categories>`,
*   and in the list, the page the visitor asked for.

The list reads the page and the arguments its page links carry before this
event. The map restricts the demand to the partners it can draw after this
event. Neither content element dispatches the event for the POST of the filter
form, which is answered with a redirect first, and the partnership content
elements do not dispatch it at all.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns or does
    *   -   :php:`getDemand()`
        -   The :php:`\FGTCLB\AcademicPartners\Domain\Model\Dto\PartnerDemand`
            the plugin built. A listener changes it in place.
    *   -   :php:`setDemand()`
        -   Replaces the demand. The demand a listener hands back is the one
            that is queried and assigned to the view as :html:`{demand}`.
    *   -   :php:`getPluginControllerActionContext()`
        -   The request, the site and its language, the content object, the
            settings of the content element, and the plugin name, :php:`List`
            or :php:`Map`.

The demand offers:

..  list-table::
    :header-rows: 1

    *   -   Methods
        -   Meaning
    *   -   :php:`getPages()`, :php:`setPages()`
        -   The uids of the pages the partner pages sit on, compared with their
            :sql:`pid`. An empty list means every partner page of the
            installation.
    *   -   :php:`getFilterCollection()`, :php:`setFilterCollection()`
        -   The categories a partner has to carry, all of them, as a
            :php:`\FGTCLB\CategoryTypes\Collection\FilterCollection`, or
            :php:`null` for no category filter.
    *   -   :php:`getShowHiddenRecords()`, :php:`setShowHiddenRecords()`
        -   Whether hidden partner pages are included.
    *   -   :php:`getSorting()`, :php:`setSorting()`, and the field and
            direction on their own through :php:`getSortingField()`,
            :php:`setSortingField()`, :php:`getSortingDirection()` and
            :php:`setSortingDirection()`
        -   The order, one of the values of
            :php:`\FGTCLB\AcademicPartners\Enumeration\SortingOptions`, for
            example `title asc`. A value that is none of them is ignored
            without a word.
    *   -   :php:`getCurrentPage()`
        -   The page of a paginated list. It is read before the event, so
            setting it has no effect.

Example
=======

A site does not want hidden partners on its maps, whatever an editor switches on
in a content element. The partner list may still show them. A listener switches
:php:`showHiddenRecords` off for the map:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/KeepHiddenPartnersOffTheMap.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class KeepHiddenPartnersOffTheMap
    {
        #[AsEventListener(identifier: 'my-extension/keep-hidden-partners-off-the-map')]
        public function __invoke(ModifyPartnerDemandEvent $event): void
        {
            // The plugin name the map is registered with, not the CType.
            if ($event->getPluginControllerActionContext()->getPluginName() !== 'Map') {
                return;
            }
            $event->getDemand()->setShowHiddenRecords(false);
        }
    }

A listener that restricts the partners to one storage folder instead sets the
pages. :php:`setPages()` becomes :sql:`pid IN (...)`, so this is one folder and
not a page tree:

..  code-block:: php

    $event->getDemand()->setPages([42]);

..  _developers-partner-events-rules:

Rules and pitfalls
==================

..  warning::

    **The map still leaves out partners without coordinates.** A partner
    without a coordinate would be drawn at 0/0 rather than be left out, so
    :php:`mapAction()` restricts the demand to drawable partners *after* the
    demand event, together with the switch :guilabel:`Show on map`. A listener
    that hands back a fresh demand, with the restriction off again, therefore
    does not bring them onto the map. This is deliberate and is covered by a
    test.

    It is not the only thing a listener cannot undo. The page type is pinned
    unconditionally, :php:`showHiddenRecords` reaches the :php:`disabled` flag
    alone (start and end time, :php:`fe_group` and :php:`deleted` stay in
    effect), and a :php:`uid` tiebreaker is always appended to the ordering.
    Everything else is open: the demand *is* the query here, not a constraint
    added to one, so a listener widens as easily as it narrows and nothing
    guards it. :php:`setShowHiddenRecords(true)` shows hidden partner pages to
    every visitor, :php:`setPages([])` drops the storage restriction the editor
    chose in the content element, and :php:`setSorting()` overrides the
    editor's ordering for every element that renders, though only for a value
    that is one of the :php:`SortingOptions` constants, since anything else is
    ignored without a word. Two listeners that disagree are resolved by the
    order they run in, the last one wins.

**A replaced demand starts from the defaults.** :php:`setDemand()` is there for
a listener that builds its own demand, and such a demand carries none of what
the plugin put in the one it was handed: the storage pages and the
:php:`showHiddenRecords` choice of the editor, and the two the visitor can set
themselves through the filter form, the :php:`sorting` and the
:php:`filterCollection`. A replacement therefore resets the ordering and the
category filter under them. Mutate the demand where that is enough, and carry
:php:`getPages()`, :php:`getShowHiddenRecords()`, :php:`getFilterCollection()`
and :php:`getSorting()` over where it is not.

**The page is not the listener's.** The page, and the filter and sorting the
page links carry, are read from the demand the request asked for, **before** the
demand event, as the filter redirect reads its URL. A listener that replaces the
demand therefore keeps the page the visitor asked for, and a listener cannot
choose the page. What a listener changes does not travel in the page links: it
acts again on the request a link leads to.

**The form shows what the listener set.** The demand a listener hands back is
the one assigned to the view, so the filter form shows its categories as
selected and its sorting as chosen, and the active filter tags list its
categories.

Introduced in
=============

*   :ref:`feature-list-plugin-events` introduced the event.
*   :ref:`breaking-1791043406` made the controller final, the event replaces a
    subclass that changed the filter or the sorting.
*   :ref:`feature-1790226100` moved the filter submission to a redirect, which
    is why the event never sees the POST of the form.
