..  _breaking-partners-record-and-category-icons-follow-the-colour-scheme:

========================================================
Breaking: Icons are renamed and follow the colour scheme
========================================================

Description
===========

The record icons of this extension were registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which renders
the default markup - the markup a :php:`typeicon_classes` entry reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said, and a dark drawing stayed dark on
the dark cards of the record list.

They are now registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups, and the files themselves are drawn in
`currentColor` with no colour of their own.

Every icon of this extension is now a `Font Awesome Free
<https://fontawesome.com>`__ solid drawing, and every icon identifier of this
extension follows the scheme
`tx-<extension key without underscores>-<group>-<name>`. The group decides the
registry: `doktype`, `plugin` and `record` icons are shown by the
backend and live in :file:`Configuration/Icons.php` only. The identifiers of
2.x are removed without an alias:

..  list-table::
    :header-rows: 1

    *   -   2.x identifier
        -   3.0 identifier
        -   Registry
        -   Shown for
    *   -   :php:`academic-partners`
        -   :php:`tx-academicpartners-doktype-partner`
        -   :file:`Configuration/Icons.php`
        -   the academic partner page type, and the partner pages offered in
            the partner select of a partnership
    *   -   :php:`academic-partners`
        -   :php:`tx-academicpartners-plugin-list`
        -   :file:`Configuration/Icons.php`
        -   the :guilabel:`Partner List` content element and its entry in the
            new content element wizard
    *   -   :php:`academic-partners`
        -   :php:`tx-academicpartners-plugin-map`
        -   :file:`Configuration/Icons.php`
        -   the :guilabel:`Partner Map` content element and its entry in the
            new content element wizard
    *   -   :php:`academic-partners`
        -   :php:`tx-academicpartners-plugin-partners`
        -   :file:`Configuration/Icons.php`
        -   the :guilabel:`Partners Linked` content element and its entry in
            the new content element wizard
    *   -   :php:`academic-partners`
        -   :php:`tx-academicpartners-plugin-partnerships-teaser`
        -   :file:`Configuration/Icons.php`
        -   the :guilabel:`Partner Logo Teaser` content element and its entry
            in the new content element wizard
    *   -   :php:`tx_academicpartners_domain_model_partnership`
        -   :php:`tx-academicpartners-record-partnership`
        -   :file:`Configuration/Icons.php`
        -   partnership records
    *   -   :php:`tx_academicpartners_domain_model_role`
        -   :php:`tx-academicpartners-record-role`
        -   :file:`Configuration/Icons.php`
        -   role records

The page type and the :guilabel:`Partners Linked` content element share one
drawing, :file:`Resources/Public/Icons/plugin/partners.svg`, but no longer one
identifier, so a site can replace either one alone. The other three content
elements are drawn from :file:`plugin/list.svg`, :file:`plugin/map.svg` and
:file:`plugin/partnerships-teaser.svg`. The two record icons are
drawn from the shared files :file:`info/partnership.svg` and
:file:`info/role.svg` of :guilabel:`academic_base`. The partner select of a
partnership showed the partnership icon for every partner page it offers, it
now shows the page type icon of the page.

The four category type icons and the group icon keep their identifiers,
:php:`category_types.partners.*` and :php:`category_types_group.partners`,
which :guilabel:`category_types` registers in the icon registry of the backend
and in the frontend icon registry of :guilabel:`academic_base`. Their files
moved:

..  list-table::
    :header-rows: 1

    *   -   Identifier
        -   2.x file
        -   3.0 file
    *   -   :php:`category_types.partners.region`
        -   :file:`CategoryTypes/Region.svg`
        -   :file:`category-type/region.svg`
    *   -   :php:`category_types.partners.partner_type`
        -   :file:`CategoryTypes/PartnerType.svg`
        -   :file:`category-type/partner-type.svg`
    *   -   :php:`category_types.partners.collaboration_type`
        -   :file:`CategoryTypes/CollaborationType.svg`
        -   :file:`EXT:academic_base/Resources/Public/Icons/info/partnership.svg`
    *   -   :php:`category_types.partners.sdg`
        -   :file:`CategoryTypes/Sdg.svg`
        -   :file:`category-type/sdg.svg`
    *   -   :php:`category_types_group.partners`
        -   :file:`CategoryGroups/Partners.svg` (3.0 development only)
        -   :file:`plugin/partners.svg`

Paths without an extension prefix are below
:file:`EXT:academic_partners/Resources/Public/Icons/`. The files
:file:`Partnership.svg`, :file:`Role.svg` and the folders
:file:`CategoryTypes/` and :file:`CategoryGroups/` are removed.
:file:`Extension.svg` stays the extension icon and is no longer registered as
an icon. The four category types and the group ask for the inlining provider
with `inlineIcon: true` in :file:`Configuration/CategoryTypes.yaml`. Without
that flag a category type icon keeps the core provider.

Impact
======

The four category type icons reach the **frontend**, through the icon
ViewHelper of :guilabel:`academic_base`,
:html:`<ab:icon identifier="category_types.partners.{type}" />`, in
:file:`Partials/Partner/Page/Categories.html`,
:file:`Partials/Partnerships/List/Item.html` and
:file:`Partials/Partnerships/Teaser/Item.html`, and through
:html:`category_types.partners.{category}` in :file:`Partials/Partner/Item.html`.
:guilabel:`category_types` registers them in the frontend icon registry as
well, with the same provider, see
:ref:`important-partners-category-icons-come-from-the-frontend-icon-registry`.
None of those calls asks for the `inline` markup, so their rendered markup
changes: an :html:`<img>` of a fixed pixel size becomes an inlined
:html:`<svg>` with :html:`width="1em" height="1em"`, which follows the font size
and the colour of the text around it. Every frontend icon of this extension
renders inline in `currentColor`, and every site using the partner or
partnership plugins sees those icons resize, recolour and change their drawing.

Site CSS or JavaScript that sized, coloured or addressed the :html:`<img>` has
to address the :html:`<svg>` instead.

In the backend, the academic partner page type icon, the content element icons,
the two record icons and the four category type icons take the text colour
around them, so they stay legible in a dark backend colour scheme, and all of
them show their new drawing.

A site package that registered one of the 2.x identifiers in its own
:file:`Configuration/Icons.php` to replace the shipped drawing replaces
nothing any more: the backend shows the new shipped icon. Backend code, TCA or
page TSconfig of a site that names a 2.x identifier, for example a
:typoscript:`iconIdentifier` of a wizard entry, shows TYPO3's not-found icon.
A site package that pointed at one of the removed files, in its own icon
registration or in a :file:`CategoryTypes.yaml` that declares a type or the
group again, shows no icon.

Affected Installations
======================

Every installation of this extension. Installations that render the partner or
partnership plugins in the frontend are affected visibly. Installations whose
site package replaces an icon of this extension, names one of its 2.x
identifiers or points at one of its removed files have to migrate.

Migration
=========

Replace an image selector with an element selector in the site CSS, for example

..  code-block:: css

    /* before */
    .partner-attributes .icon img { width: 32px; }

    /* after */
    .partner-attributes .icon svg { width: 1.25em; }

The icon element keeps the surrounding
:html:`<span class="t3js-icon icon" data-identifier="…">` wrapper, so a
selector written against the wrapper of a category type icon needs no change.
A selector written against the identifier of a page type, content element or
record icon names the 3.0 identifier from the table above.

Replace every 2.x identifier the site names, in TCA, page TSconfig, templates
or code, with its 3.0 identifier. The 2.x :php:`academic-partners` became five
identifiers, pick the one of the page type or of the content element it is
shown for.

A site package that replaces a page type, content element or record icon
registers the 3.0 identifier in its own :file:`Configuration/Icons.php`, for
the backend:

..  code-block:: php
    :caption: EXT:my_site/Configuration/Icons.php

    use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

    return [
        'tx-academicpartners-plugin-partners' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_site/Resources/Public/Icons/Partners.svg',
        ],
    ];

A site package that replaces a category type icon keeps the identifier: it
declares the type again in its :file:`Configuration/CategoryTypes.yaml` with
its own :yaml:`icon`, for both registries, or registers the identifier in its
own :file:`Configuration/FrontendIcons.php`, for the frontend only, see
:ref:`important-partners-category-icons-come-from-the-frontend-icon-registry`.
A file path of this extension it names has to be the 3.0 path from the table
above.

.. index:: Backend, Frontend, ext:academic_partners
