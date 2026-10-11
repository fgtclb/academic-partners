..  _breaking-removed-unused-autocomplete-library:

=================================================
Breaking: Removed the unused autocomplete library
=================================================

Description
===========

The file
:file:`EXT:academic_partners/Resources/Public/JavaScript/autocomplete.min.js`
has been removed. It was a minified third party library, publishing a global
:js:`Autocomplete`, that arrived with the initial import of the extension and
was never referenced. No template, no TypoScript, no PHP and no other asset
loaded it.

It was shipped in every release regardless, and a vendored, minified file
without a source is a maintenance and security liability for as long as it
exists.

Impact
======

Nothing in the extension changes. No plugin, template or partial loaded the
file, so no rendering, styling or behaviour is affected.

The file is no longer part of the package, and a request for its public URL
returns 404.

Affected Installations
======================

Only installations that referenced the file from their own site package or
template overrides. That is possible, because the file was located below
:file:`Resources/Public/`, but it was never part of the public API of the
extension and no documentation mentioned it.

Migration
=========

A project that loads the file from its own template ships the library in its
own site package, taken from version 2.3 of this extension or from the
library's own release, and references it from there:

..  code-block:: html

    <f:asset.script identifier="autocomplete" src="EXT:my_sitepackage/Resources/Public/JavaScript/autocomplete.min.js" />

No migration is required otherwise.

..  index:: Frontend, JavaScript, ext:academic_partners
