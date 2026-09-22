..  _feature-list-plugin-events:

=================================================
Feature: Change the partner list and map by event
=================================================

Description
===========

:php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerDemandEvent` and
:php:`\FGTCLB\AcademicPartners\Event\ModifyPartnerListEvent` are PSR-14 events
dispatched by both the partner list and the partner map.

The demand event fires after the demand has been built from the content element
settings and the request and before the partners are queried; the demand a
listener hands back is the one that is queried and assigned to the view. The
list event fires after the query and before the view variables are assigned; a
listener replaces the partners, replaces the applicable categories, or assigns
further view variables.

Both carry
:php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`,
so a listener knows the request, the site, the content element, its settings and
whether the list or the map is rendering.

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

Impact
======

Nothing changes in an installation that has no listener. A project that
subclasses :php:`PartnerController` to adjust the demand, the result or the view
variables can drop the subclass and the plugin re-registration that goes with
it, and listen instead.

Two details are worth knowing. The map restricts the demand to partners that can
be drawn **after** the demand event, because a partner without coordinates would
be drawn at 0/0 rather than left out, so a listener cannot bring those onto the
map. And the applicable categories are computed once, before the list event, and
not recomputed afterwards - a listener that replaces the partners and wants the
filter to match them sets the categories as well.

..  warning::

    A demand listener widens as easily as it narrows. The demand *is* the
    query, so :php:`setShowHiddenRecords(true)` shows hidden partner pages to
    every visitor, :php:`setPages([])` drops the storage restriction the editor
    chose, and :php:`setSorting()` overrides the editor's ordering. What a
    listener cannot undo is the page type, the enable fields other than
    :php:`disabled`, the :php:`uid` tiebreaker of the ordering and the drawable
    restriction of the map. A result handed to :php:`setPartners()` is rendered
    in the order it carries - the ordering of the repository is not reapplied -
    and a demand built from scratch rather than mutated drops the editor's
    settings and the category filter the visitor submitted.

..  index:: Frontend, PHP-API, ext:academic_partners
