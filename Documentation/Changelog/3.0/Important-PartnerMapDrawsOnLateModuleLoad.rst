.. _important-partner-map-draws-on-late-module-load:

===================================================
Important: Partner map draws on a late module load
===================================================

Description
===========

The partner map did not draw on most page loads. The container stayed empty,
no tiles, no markers, and nothing was reported anywhere - not in the browser
console, not in the TYPO3 log.

:file:`Resources/Private/TypeScript/frontend/map.ts` bound its whole body to
:js:`document.addEventListener('DOMContentLoaded', ...)` without asking whether
that event was still ahead of it.

:html:`f:asset.module` renders every module with :html:`async`: the ViewHelper
accepts an ``identifier`` and nothing else, and
:php:`TYPO3\CMS\Core\Page\JavaScriptRenderer` writes the attribute
unconditionally. An async module is not ordered against document parsing, so it
regularly runs after :js:`DOMContentLoaded` has already fired. The listener was
then registered on an event that never comes again.

The module now runs its initialisation immediately when the document has
finished parsing, and waits only while it has not:

..  code-block:: typescript

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMap, { once: true });
    } else {
        initializeMap();
    }

That is the shape the other frontend modules of this repository already use.
This was the only one missing it.

Impact
======

The map draws on every load. Nothing about its markup, its configuration or
its data changed, and an installation that saw the map draw before - the load
where the module happened to win the race - sees no difference.

Affected Installations
======================

Every installation rendering the :guilabel:`Partners Map` plugin, or a template
that includes the map module.

.. index:: Frontend, JavaScript, ext:academic_partners
