..  _important-partner-map-draws-on-late-module-load:

===========================================================
Important: The partner map draws when its script loads late
===========================================================

Description
===========

The script of the partner map,
:file:`EXT:academic_partners/Resources/Public/JavaScript/frontend/map.js`, bound
its whole body to :js:`document.addEventListener('DOMContentLoaded', ...)`
without asking whether that event was still ahead of it. A script that runs
after the document has been parsed registered its listener on an event that
never comes again: the map container stayed empty, without tiles and markers,
and nothing was reported, neither in the browser console nor in the TYPO3 log.

The template shipped with the extension loads the script with
:html:`<f:asset.script>` and without :html:`async`, as a classic script that
runs while the document is parsed. It was therefore not affected. A script
loaded with :html:`async`, or injected after parsing by a consent manager or a
tag manager, was.

The body now runs immediately when the document has finished parsing, and
waits for :js:`DOMContentLoaded` only while it has not.

Impact
======

The map draws however the script is loaded. An installation that renders the
map with the shipped template sees no difference.

Affected Installations
======================

Installations that load the map script asynchronously or late, through a
template override or their own asset handling. Nothing has to be done on
update. A workaround that forced the script to load early can be dropped.

..  index:: Frontend, JavaScript, ext:academic_partners
