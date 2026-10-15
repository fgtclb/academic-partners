..  _developers-event-modify-partner-list-event:

======================
ModifyPartnerListEvent
======================

:php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent` hands a listener
the partners the :guilabel:`Partner List` or the :guilabel:`Partner Map` found,
before they are rendered. A listener replaces them, replaces the categories the
filter form offers, or hands further variables to the template.

When it is dispatched
=====================

In :php:`PartnerController::listAction()` and
:php:`PartnerController::mapAction()`, on every request that renders one of the
two content elements, after the partners are queried with the demand of
:ref:`ModifyPartnerDemandEvent <developers-event-modify-partner-demand-event>`
and the categories of the filter are computed from them, and before the view
variables are assigned. In the list it comes before the pagination is built, in
both content elements before
:ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns or does
    *   -   :php:`getPartners()`, :php:`setPartners()`
        -   Every partner the content element found, an Extbase query result of
            :php:`\FGTCLB\AcademicPartners\Domain\Model\Partner`. In the list
            these are all partners, not one page of them. The template
            receives the result a listener hands back as :html:`{partners}`.
    *   -   :php:`getCategories()`, :php:`setCategories()`
        -   The categories the filter form offers, a
            :php:`\FGTCLB\CategoryTypes\Collection\CategoryCollection`. A
            category no listed partner carries is an option that is disabled,
            or left out with the site setting
            :typoscript:`filter.hideDisabledOptions`.
    *   -   :php:`getDemand()`
        -   The demand the partners were queried with, read only here.
    *   -   :php:`getView()`
        -   The view of the content element. A listener assigns variables of
            its own to it.
    *   -   :php:`getPluginControllerActionContext()`
        -   The request, the site and its language, the content object, the
            settings of the content element, and the plugin name, :php:`List`
            or :php:`Map`.

Example
=======

A site shows a line above the partner list naming the countries the listed
partners come from. A partner stores the ISO code of its country, so the
listener collects the label of each country name, and the template of the site
package translates them:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddPartnerCountriesToTheList.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddPartnerCountriesToTheList
    {
        #[AsEventListener(identifier: 'my-extension/add-partner-countries-to-the-list')]
        public function __invoke(ModifyPartnerListEvent $event): void
        {
            if ($event->getPluginControllerActionContext()->getPluginName() !== 'List') {
                return;
            }
            $countries = [];
            foreach ($event->getPartners() as $partner) {
                $label = $partner->getAddressCountryLocalizedNameLabel();
                if ($label !== '') {
                    $countries[$partner->getAddressCountry()] = $label;
                }
            }
            ksort($countries);
            $event->getView()->assign('partnerCountries', array_values($countries));
        }
    }

The template override of :file:`Templates/Partner/List.html` renders each
label of :html:`{partnerCountries}` with :html:`<f:translate key="{label}" />`,
and a partial rendered with :html:`{_all}` gets the variable as well. The list
is ordered by country code, not by the translated name.

..  _developers-event-modify-partner-list-event-rules:

Rules and pitfalls
==================

**The variables of the plugin are assigned after the event.** The controller
assigns :html:`{partners}`, :html:`{categories}`, :html:`{demand}`,
:html:`{data}`, :html:`{record}`, :html:`{filterTypes}` and
:html:`{visitorSelection}` once the listeners have run, and the list adds the
variables of the pagination. A listener that assigns one of these names through
:php:`getView()` is overwritten. Use :php:`setPartners()` and
:php:`setCategories()` for the first two, and names of your own for anything
else.

**A replaced result is rendered as it is.** :php:`setPartners()` takes whatever
query result a listener hands back, and the ordering of the repository is not
reapplied to it. A listener that builds its own result gives it its own
ordering, or the list is in whatever order the database happens to return,
which is not the same list twice on PostgreSQL.

**The categories are not recomputed.** They are computed from the queried
partners, once, before the list event. A listener that replaces the partners
and wants the filter of the plugin to match them sets the categories too:

..  code-block:: php

    $event->setPartners($narrowedResult);
    $event->setCategories(
        $this->categoryRepository->findAllApplicable('partners', ...$narrowedResult->toArray()),
    );

:php:`findAllApplicable()` of
:php:`\FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository` is what the
controller itself calls: it keeps every category of the group and marks the ones
no record carries as disabled options. :php:`findByGroupAndUidList()` returns a
bare list instead, so a listener that reaches for that one drops the disabled
options the filter otherwise shows.

**A paginated list pages the result the list event hands back.** The event
sees every partner the list found, not one page of them. With the
:ref:`pagination <configuration-list-pagination>` enabled, the controller
builds the paginator after the event, from the result the event hands back, and
the paginator cuts a page by running that result's query again with a limit and
an offset. A result a listener hands back is therefore paged by its query, not
by the records it holds. :html:`{partners}` stays the whole result, the page is
:html:`{paginator.paginatedItems}`.

**The result count counts the result handed back.** The number of partners
found, :ref:`when the site shows it <configuration-active-filters>`, is the
number of partners in :html:`{partners}`, so it follows a replaced result.

Introduced in
=============

*   :ref:`feature-list-plugin-events` introduced the event.
*   :ref:`breaking-1791043406` made the controller final, the event replaces a
    subclass that changed the result or added view variables.
*   :ref:`feature-1790272109` added the pagination, which pages the result the
    event hands back.
