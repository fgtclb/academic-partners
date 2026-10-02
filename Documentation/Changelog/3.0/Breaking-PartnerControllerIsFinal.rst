.. _breaking-1791043406:

=========================================
Breaking: The partner controller is final
=========================================

Description
===========

:php:`\FGTCLB\AcademicPartners\Controller\PartnerController` is :php:`final`.
It serves the partner list (`List`), the partner map (`Map`), the partnerships
list (`PartnershipsList`) and the partnerships teaser (`PartnershipsTeaser`).

Its collaborators are private constructor arguments now. The methods
:php:`injectFilterRedirectExtensionService()` and
:php:`injectFilterTypeResolver()` are removed, and
:php:`redirectFilterSubmission()` and :php:`pluginControllerActionContext()`
are private. All of them existed only so that a subclass could keep calling the
old constructor and the helpers of the shipped actions.

Every plugin controller of the academic extensions is final in 3.0. A plugin is
extended through its events, not through a subclass of its controller.

Impact
======

A class that extends :php:`PartnerController` stops loading with a fatal
error, :php:`Class ... cannot extend final class
FGTCLB\AcademicPartners\Controller\PartnerController`. That happens as soon as
anything loads the subclass, a plugin registered with it renders, or the
container is built with it.

An XCLASS of the controller fails the same way. The upgrade check
:bash:`academic:upgrade:check` of :guilabel:`EXT:academic_base` reports it as
an error. A :php:`configurePlugin()` call that points one of the four plugins
at a subclass is not reported.

The plugins, their templates and their settings are unchanged. The behaviour
is the same on TYPO3 v13 and v14.

Affected Installations
======================

Installations with a class that extends :php:`PartnerController`, registered
for a plugin through :php:`ExtensionUtility::configurePlugin()`, as an XCLASS,
or in the service container.

Migration
=========

Remove the subclass, and the :php:`configurePlugin()` call or the XCLASS
registration that points at it, so the shipped controller serves the plugin
again. Move each override to its replacement:

*   Changing the filter or the sorting before the query: a listener of
    :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent`, which
    adjusts or replaces the demand. See :ref:`feature-list-plugin-events`. The
    demand accepts only the sorting options of this extension and cannot
    express a sorting by another field, :sql:`tstamp` for instance. A listener
    of the list event can hand back a result of its own query sorted that way.
*   A replaced or reduced result, other filter categories, or additional view
    variables of the list and the map: a listener of
    :php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent`.
*   Additional view variables of the partnerships list and teaser: a listener
    of the plugin view event of :guilabel:`academic_base`, see
    :ref:`feature-plugin-view-event`.
*   Pagination of the partner list: the :guilabel:`Pagination` tab of the
    content element, see :ref:`feature-1790272109`.
*   Filter selections that can be bookmarked: the list and the map answer a
    filter submission with a redirect to a GET URL, see
    :ref:`feature-1790226100`.

A subclass that adds a view variable:

..  code-block:: php
    :caption: Before: EXT:my_extension/Classes/Controller/PartnerController.php

    final class PartnerController extends \FGTCLB\AcademicPartners\Controller\PartnerController
    {
        public function listAction(?array $demand = null): ResponseInterface
        {
            $this->view->assign('countries', $this->countryRepository->findAll());
            return parent::listAction($demand);
        }
    }

does the same as a listener:

..  code-block:: php
    :caption: After: EXT:my_extension/Classes/EventListener/AddCountriesToPartnerList.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent;
    use MyVendor\MyExtension\Domain\Repository\CountryRepository;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final readonly class AddCountriesToPartnerList
    {
        public function __construct(
            private CountryRepository $countryRepository,
        ) {}

        #[AsEventListener(identifier: 'my-extension/add-countries-to-partner-list')]
        public function __invoke(ModifyPartnerListEvent $event): void
        {
            $event->getView()->assign('countries', $this->countryRepository->findAll());
        }
    }

The listener acts on the list and the map alike. It reads
:php:`$event->getPluginControllerActionContext()->getPluginName()` where it
means only one of them.

..  index:: Frontend, PHP-API, NotScanned, ext:academic_partners
