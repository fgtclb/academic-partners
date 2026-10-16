..  _important-ace-933-academic-partners:

===================================================================
Important: The partner map allows its tiles under a security policy
===================================================================

Description
===========

The :guilabel:`Partners Map` content element loads the tiles of its map as
images from a tile server, OpenStreetMap by default. The frontend Content
Security Policy of TYPO3 allows images from the site itself, :html:`data:` URLs
and the preview images of YouTube and Vimeo only. Once a site enforced that
policy, with the feature :php:`security.frontend.enforceContentSecurityPolicy`
for example, the browser blocked every tile, and the markers stayed on an empty
map.

The element now extends the directive `img-src` of the policy of the page it is
rendered on by the host of the tile URL the map uses. A host label with a
placeholder becomes a wildcard, so the default
`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` adds
`https://*.tile.openstreetmap.org`. A page without the element gets no source.
The source is registered while TYPO3 renders the page and is kept with the page
in the page cache, so a cached page sends it as well.

Impact
======

*   On a site that enforces the frontend policy of TYPO3, the map shows its
    tiles again.
*   A site that added the host of the tile server to `img-src` in its
    :file:`csp.yaml` can keep that rule or remove it. The source is not added
    twice.
*   On TYPO3 13 and newer, a site that renders the element without the data
    processors of :typoscript:`lib.contentElement` adds the host of the tile
    server in its :file:`csp.yaml`:

    ..  code-block:: yaml
        :caption: config/sites/<site>/csp.yaml

        mutations:
          - mode: extend
            directive: 'img-src'
            sources:
              - 'https://*.tile.openstreetmap.org'

*   A policy that the web server or a proxy sends is not covered. The browser
    enforces it next to the one of TYPO3, or in its place, so it needs the
    same source itself.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, TypoScript, ext:academic_partners
