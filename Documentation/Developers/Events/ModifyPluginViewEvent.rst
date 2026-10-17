..  _developers-event-modify-plugin-view-event:

=====================
ModifyPluginViewEvent
=====================

:php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent` belongs to
:guilabel:`academic_base`, and the plugins of seven academic extensions
dispatch it. It hands a listener the view of a plugin once the plugin has
assigned its own variables, so a listener adds a variable to the templates
without replacing the controller. The event itself, and the rules that apply to
every plugin, are described on :ref:`its page in the manual of academic_base
<fgtclb/academic-base:developers-event-modify-plugin-view-event>`.
This page describes what the content elements of :guilabel:`academic_partners`
pass in.

When it is dispatched
=====================

All four content elements dispatch it once each time they render, as the last
step of their action:

..  list-table::
    :header-rows: 1

    *   -   Content element
        -   Plugin name
        -   Action
        -   Dispatched after
    *   -   :ref:`Partner List <integrator-partner-list>`
        -   :php:`List`
        -   :php:`list`
        -   :ref:`ModifyPartnerListEvent <developers-event-modify-partner-list-event>`
            and the pagination
    *   -   :ref:`Partner Map <integrator-partner-map>`
        -   :php:`Map`
        -   :php:`map`
        -   :ref:`ModifyPartnerListEvent <developers-event-modify-partner-list-event>`
    *   -   :ref:`Partners Linked <integrator-partnerships-list>`
        -   :php:`PartnershipsList`
        -   :php:`partnershipsList`
        -   reading the partnerships of the page
    *   -   :ref:`Partner Logo Teaser <integrator-partnerships-teaser>`
        -   :php:`PartnershipsTeaser`
        -   :php:`partnershipsTeaser`
        -   reading the partnerships of the page

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the content element: the extension name
            :php:`AcademicPartners`, the plugin and action name of the table
            above, the request, the site and its language, the content object
            and the settings of the content element, its FlexForm merged over
            :typoscript:`plugin.tx_academicpartners.settings`.
    *   -   :php:`getView()`
        -   The view, with the variables the action assigned.

The variables a listener finds in the view:

..  list-table::
    :header-rows: 1

    *   -   Content element
        -   Variables
    *   -   Partner List
        -   :html:`{partners}`, :html:`{categories}`, :html:`{demand}`,
            :html:`{filterTypes}`, :html:`{visitorSelection}`, :html:`{data}`,
            :html:`{record}`, and with the pagination enabled
            :html:`{paginator}`, :html:`{pagination}` and
            :html:`{demandArguments}`
    *   -   Partner Map
        -   the same, without the pagination
    *   -   Partners Linked, Partner Logo Teaser
        -   :html:`{partnerships}`, :html:`{partnershipRoles}`,
            :html:`{partnershipsWithoutRole}`, :html:`{data}`, :html:`{record}`

Example
=======

A site shows a link to its page of all partners below the logo teaser of a
project page. The uid of that page is a site setting of the site package, and a
listener hands it to the template of the teaser:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddAllPartnersPageToTheTeaser.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AddAllPartnersPageToTheTeaser
    {
        #[AsEventListener(identifier: 'my-extension/add-all-partners-page-to-the-teaser')]
        public function __invoke(ModifyPluginViewEvent $event): void
        {
            $context = $event->getPluginControllerActionContext();
            if ($context->getControllerExtensionName() !== 'AcademicPartners'
                || $context->getPluginName() !== 'PartnershipsTeaser'
            ) {
                return;
            }
            $pageUid = (int)($context->getSite()?->getSettings()->get('myExtension.allPartnersPage') ?? 0);
            if ($pageUid > 0) {
                $event->getView()->assign('allPartnersPage', $pageUid);
            }
        }
    }

An override of :file:`Templates/Partner/PartnershipsTeaser.html` renders the
link with :html:`<f:link.page pageUid="{allPartnersPage}">`.

Rules and pitfalls
==================

**Check the extension name and the plugin name.** One listener receives the
event of every academic plugin that dispatches it. :php:`List` is the plugin name of the partner
list and of plugins of other academic extensions, so the extension name tells
them apart.

**A replaced variable is replaced in the view only.** A listener that assigns
:html:`{partners}` again changes what the template renders, not the pagination,
which was built from the partners before, nor the categories of the filter. A
change of which partners are shown belongs in
:ref:`ModifyPartnerListEvent <developers-event-modify-partner-list-event>`.

**The partnership content elements are cached.** :guilabel:`Partners Linked`
and :guilabel:`Partner Logo Teaser` are rendered when the page is rendered, and
the page is cached with what the listener assigned, so a listener cannot assign
something different for different visitors there. The partner list and the map
are not cached, a listener runs on every request that renders them.

Introduced in
=============

*   :ref:`feature-plugin-view-event` made the four content elements dispatch
    the event.
