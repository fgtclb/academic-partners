..  _feature-1791566502:

==========================================================
Feature: The script of the partner map can be switched off
==========================================================

Description
===========

The partial :file:`Partner/Map.html` registers the module
:js:`@fgtclb/academic-partners/frontend/map.js` and the stylesheets of the map
libraries it imports, Leaflet and Leaflet.markercluster. An installation that
draws the map with a script of its own had to override the partial to leave
them out.

The module and the stylesheets of the libraries are switchable now, together,
per site:

..  list-table::
    :header-rows: 1

    *   -   Site setting and constant
        -   Default
    *   -   :yaml:`plugin.tx_academicpartners.assets.js`
        -   :yaml:`true`

It is declared by the site set `fgtclb/academic-partners-map`, next to the other
settings of the map:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    plugin.tx_academicpartners.assets.js: false

An installation that configures its frontend through :sql:`sys_template`
records sets the TypoScript constant of the very same name, in
:guilabel:`Constants`:

..  code-block:: typoscript
    :caption: Constants of the root sys_template record

    plugin.tx_academicpartners.assets.js = 0

Every academic extension that loads a script has this switch, named
:typoscript:`assets.js` below its own plugin namespace and switched on by
default, after the switch of the study plan content element.

Impact
======

Nothing changes for an installation that configures nothing: the map loads
its script and the stylesheets of the libraries exactly as before.

Switched off, the page loads neither, and the markup is unchanged: the map
element with its settings as data attributes, and the hidden list of the
partners. **The map stays empty** until a script of the site draws it from
them, see :ref:`configuration-javascript`.

The partial takes the switch as the new argument :html:`assets`. The
:guilabel:`Partners Map` content element passes it, and the data processor
`partner-data` adds it to the page of the page type :guilabel:`Academic
partner` as :html:`{mapAssets}`, next to :html:`{mapSettings}`:

..  code-block:: html

    <f:render partial="Partner/Map" arguments="{partner: partner, map: mapSettings, assets: mapAssets}" />

A page template that renders the partial without the argument loads the
script whatever the setting says, as it did before. A project that overrides
:file:`Partials/Partner/Map.html` keeps whatever its copy loads.

.. index:: Frontend, Fluid, TypoScript, ext:academic_partners
