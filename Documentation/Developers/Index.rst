..  _developers:

==============
For developers
==============

The partner list and the partner map dispatch two PSR-14 events each. They are
the supported way to change what a plugin queries and what it renders, and they
replace the only alternative there used to be: subclassing
:php:`PartnerController` and re-registering the plugin.

..  _developers-partner-events:

The two events
==============

..  list-table::
    :header-rows: 1

    *   -   Event
        -   Dispatched
        -   A listener may
    *   -   :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent`
        -   in :php:`listAction()` and :php:`mapAction()`, after the demand is
            built from the content element settings and the request and before
            the partners are queried
        -   replace the demand
    *   -   :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent`
        -   in both actions, after the query and before the view variables are
            assigned
        -   replace the partners, replace the applicable categories, assign
            further view variables

Both carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
through :php:`getPluginControllerActionContext()`: the request, the site and
its language, the content object of the element, the settings of the content
element and the plugin name.

Neither event fires for a submission of the filter and sorting form: the plugin
answers the POST with a redirect to a URL carrying the selection before the
demand event, and both events fire on the request that follows, with the
selection in the query string (or the route), not in the parsed body. See
:ref:`feature-1790226100`.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RestrictPartnersToOneFolder.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class RestrictPartnersToOneFolder
    {
        #[AsEventListener(identifier: 'my-extension/restrict-partners-to-one-folder')]
        public function __invoke(ModifyPartnerDemandEvent $event): void
        {
            // The pages the partner records sit *on*: `setPages()` becomes
            // `pid IN (...)`, so this is one storage folder and not a page tree.
            $event->getDemand()->setPages([42]);
        }
    }

The plugin name is the name the plugin was **registered** with -
:php:`List` for the list and :php:`Map` for the map - not the content element
type. It is how one listener serves both and acts on one:

..  code-block:: php

    if ($event->getPluginControllerActionContext()->getPluginName() !== 'Map') {
        return;
    }

:php:`ModifyPartnerListEvent` carries the view as well, so a listener assigns
values a project template renders:

..  code-block:: php

    $event->getView()->assign('partnerCount', count($event->getPartners()->toArray()));

..  _developers-partner-events-rules:

Rules worth knowing
===================

..  warning::

    **The map still leaves out partners without coordinates.** A partner
    without a coordinate would be drawn at 0/0 rather than be left out, so
    :php:`mapAction()` restricts the demand to drawable partners *after* the
    demand event. A listener that hands back a fresh demand - with the
    restriction off again - therefore does not bring them onto the map. This is
    deliberate and is covered by a test.

    It is not the only thing a listener cannot undo - the page type is pinned
    unconditionally, :php:`showHiddenRecords` reaches the :php:`disabled` flag
    alone (start- and endtime, :php:`fe_group` and :php:`deleted` stay in
    effect), and a :php:`uid` tiebreaker is always appended to the ordering.
    Everything else is open: the demand *is* the query here, not a constraint
    added to one, so a listener widens as easily as it narrows and nothing
    guards it:
    :php:`setShowHiddenRecords(true)` shows hidden partner pages to every
    visitor, :php:`setPages([])` drops the storage restriction the editor chose
    in the content element, and :php:`setSorting()` overrides the editor's
    ordering for every element that renders - though only for a value that is
    one of the :php:`SortingOptions` constants, since anything else is ignored
    without a word. Two listeners that disagree are resolved by the order they
    run in - the last one wins.

**A replaced demand starts from the defaults.** :php:`setDemand()` is there for
a listener that builds its own demand, and such a demand carries none of what
the plugin put in the one it was handed: the :php:`showHiddenRecords` choice of
the editor, and the two the visitor can set themselves through the filter form
- the :php:`sorting` and the :php:`filterCollection` - so a replacement resets
the ordering and the category filter under them. Mutate the demand where that
is enough, and carry :php:`getPages()`, :php:`getShowHiddenRecords()`,
:php:`getFilterCollection()` and :php:`getSorting()` over where it is not.

**A replaced result is rendered as it is.** :php:`setPartners()` takes whatever
query result a listener hands back; the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return -
which is not the same list twice on PostgreSQL.

**The categories are not recomputed.** They are computed from the queried
partners, once, before the list event. A listener that replaces the partners
and wants the filter of the plugin to match them sets the categories too:

..  code-block:: php

    $event->setPartners($narrowedResult);
    $event->setCategories(
        $this->categoryRepository->findAllApplicable('partners', ...$narrowedResult->toArray()),
    );

:php:`findAllApplicable()` is what the controller itself calls: it keeps every
category of the group and marks the ones no record carries as disabled options.
:php:`findByGroupAndUidList()` returns a bare list instead, so a listener that
reaches for that one drops the disabled options the filter otherwise shows.

Nothing changes in an installation that has no listener: without one, both
plugins query and render exactly what they did before.

..  index:: Frontend, PHP-API, ext:academic_partners
