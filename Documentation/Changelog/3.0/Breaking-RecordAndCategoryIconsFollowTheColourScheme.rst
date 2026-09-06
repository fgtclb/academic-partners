..  _breaking-partners-record-and-category-icons-follow-the-colour-scheme:

============================================================
Breaking: Record and category icons follow the colour scheme
============================================================

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

That covers the three identifiers of this extension -
:php:`academic-partners`, :php:`tx_academicpartners_domain_model_partnership`
and :php:`tx_academicpartners_domain_model_role` - and its four category type
icons, which :php:`EXT:category_types` registers as
:php:`category_types.partners.*`. The four category types ask for it with
`inlineIcon: true` in :file:`Configuration/CategoryTypes.yaml`; without that
flag a category type icon keeps the core provider.

Impact
======

The four category type icons reach the **frontend**, through
:html:`<core:icon identifier="category_types.partners.{type}" />` in
:file:`Pages/AcademicPartner.html`, :file:`Partials/Partnerships/List/Item.html`
and :file:`Partials/Partnerships/Teaser/Item.html`, and through
:html:`category_types.partners.{category}` in :file:`Partials/Partner/Item.html`.
None of those calls asks for the `inline` markup, so their rendered markup
changes: an :html:`<img>` of a fixed pixel size becomes an inlined
:html:`<svg>` with :html:`width="1em" height="1em"`, which follows the font size
and the colour of the text around it. Every site using the partner or
partnership plugins sees those icons resize and recolour.

Site CSS or JavaScript that sized, coloured or addressed the :html:`<img>` has
to address the :html:`<svg>` instead.

In the backend, the academic partner page type icon, the two record icons and
the four category type icons take the text colour around them, so they stay
legible in a dark backend colour scheme. :php:`academic-partners` is also the
icon of the four content elements of this extension, so those follow with it -
the identifier is one icon, not two.

Affected Installations
======================

Every installation of this extension. Installations that render the partner or
partnership plugins in the frontend are affected visibly.

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
selector written against the wrapper needs no change.

.. index:: Backend, Frontend, ext:academic_partners
