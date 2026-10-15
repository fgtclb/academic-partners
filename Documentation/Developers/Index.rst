..  _developers:

==============
For developers
==============

The content elements of this extension dispatch PSR-14 events. They are the
supported way to change what the partner list and the partner map query and
what the four content elements render, and they replace the only alternative
there used to be: subclassing :php:`PartnerController` and re-registering the
plugin. The controller is final since version 3.0, see
:ref:`breaking-1791043406`.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.

..  _developers-partner-events:

Events
======

..  list-table::
    :header-rows: 1
    :widths: 25 30 30 15

    *   -   Event
        -   Dispatched
        -   What a listener can change
        -   Owner
    *   -   :ref:`ModifyPartnerDemandEvent <developers-event-modify-partner-demand-event>`
        -   by the :guilabel:`Partner List` and the :guilabel:`Partner Map`,
            after the demand is built from the content element and the request,
            before the partners are queried
        -   change or replace the demand
        -   this extension
    *   -   :ref:`ModifyPartnerListEvent <developers-event-modify-partner-list-event>`
        -   by the same two, after the query and before the view variables are
            assigned
        -   replace the partners and the categories of the filter, assign
            further view variables
        -   this extension
    *   -   :ref:`ModifyPluginViewEvent <developers-event-modify-plugin-view-event>`
        -   by all four content elements, after they assigned their view
            variables
        -   assign further view variables
        -   :guilabel:`academic_base`

The two events of this extension carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
through :php:`getPluginControllerActionContext()`: the request, the site and
its language, the content object of the element, the settings of the content
element and the plugin name.

The plugin name is the name the plugin was **registered** with, :php:`List` for
the list and :php:`Map` for the map, not the content element type. It is how
one listener serves both and acts on one:

..  code-block:: php

    if ($event->getPluginControllerActionContext()->getPluginName() !== 'Map') {
        return;
    }

Neither event of this extension fires for a submission of the filter and
sorting form: the plugin answers the POST with a redirect to a URL carrying the
selection before the demand event, and both events fire on the request that
follows, with the selection in the query string (or the route), not in the
parsed body. See :ref:`feature-1790226100`.

Nothing changes in an installation that has no listener: without one, the
content elements query and render exactly what they did before.

..  toctree::
    :hidden:
    :titlesonly:

    Events/ModifyPartnerDemandEvent
    Events/ModifyPartnerListEvent
    Events/ModifyPluginViewEvent

..  index:: Frontend, PHP-API, ext:academic_partners
