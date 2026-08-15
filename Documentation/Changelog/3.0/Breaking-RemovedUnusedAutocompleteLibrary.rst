..  _breaking-removed-unused-autocomplete-library:

=================================================
Breaking: Removed the unused autocomplete library
=================================================

Description
===========

The file :file:`Resources/Public/JavaScript/autocomplete.min.js` has been
removed. It was a minified third party library that arrived with the initial
import of the extension and was never referenced — no template, no TypoScript,
no PHP and no other asset loaded it.

It was shipped in every release regardless, and a vendored, minified file
without a source is a maintenance and security liability for as long as it
exists.

Impact
======

Nothing in the extension changes. No plugin, template or partial loaded the
file, so no rendering, styling or behaviour is affected.

The file is no longer part of the package and a request for it returns 404.

Affected installations
======================

Only installations that referenced the file from their own site package or
template overrides. That is possible, because the file was located below
:file:`Resources/Public/`, but it was never part of the extension's public
API and no documentation ever mentioned it.

Migration
=========

If a project loads the file from its own template, ship the library in the
project's own site package and reference it from there instead:

..  code-block:: html

    <f:asset.script identifier="autocomplete" src="EXT:my_sitepackage/Resources/Public/JavaScript/autocomplete.min.js" />

No migration is required otherwise.
