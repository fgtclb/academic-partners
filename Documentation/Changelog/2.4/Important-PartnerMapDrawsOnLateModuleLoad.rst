..  _important-partner-map-draws-on-late-module-load:

======================================================
Important: The partner map draws on a late module load
======================================================

Description
===========

The partner map did not draw on most page loads. The container stayed empty, no
tiles and no markers, and nothing was reported - not in the browser console, not
in the TYPO3 log.

The module bound its whole body to
:js:`document.addEventListener('DOMContentLoaded', ...)` without asking whether
that event was still ahead of it. :html:`<f:asset.module>` renders every module
with :html:`async`, so it is not ordered against document parsing and regularly
runs after :js:`DOMContentLoaded` has already fired - and the listener was then
registered on an event that never comes again.

The body is called immediately when the document has finished parsing, and
deferred only while it has not. That is the shape the study plan module already
used; the map was the only frontend module of this extension without it.

Impact
======

The map draws. An installation that saw it draw before - the timing decided it,
so some page loads did - sees no difference.

Affected Installations
======================

Every installation that renders the partner map plugin. Nothing has to be done
on update.

An installation that worked around this by loading the module differently, for
example without :html:`async` through a template override, can drop the
workaround.

.. index:: Frontend, JavaScript, ext:academic_partners
