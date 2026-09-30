..  _breaking-partner-page-renders-inside-the-site-layout:

=====================================================
Breaking: Partner pages render inside the site layout
=====================================================

Description
===========

The page template of the page type :guilabel:`Academic partner`,
:file:`Resources/Private/Pages/AcademicPartner.html`, declared no Fluid layout.
On a site package that renders its header, navigation and footer through a page
layout, as :composer:`bk2k/bootstrap-package` does, a partner page therefore
rendered without any of them. The template rendered every part inline, so a
project that wanted to change one of them had to replace the whole template.
And its paths were registered at the key `100` of the page object, which on a
:typoscript:`PAGEVIEW` site package replaced the package's own paths of that key
on partner pages - its layouts and partials were gone there.

From 3.0 on:

*   The template renders the section :html:`Main` of the layout named by the
    new setting :typoscript:`plugin.tx_academicpartners.page.layout`, `Default`
    by default. A site package without a layout :file:`Default` gets a fallback
    layout of this extension, which renders the section alone.
*   The section renders five partials: :file:`Partner/Page/Header.html`,
    :file:`Partner/Page/Media.html`, :file:`Partner/Page/Categories.html`,
    :file:`Partner/Page/Address.html` and :file:`Partner/Page/Content.html`.
*   The header renders the subtitle of the page. The title is wrapped in an
    element with the class `academic-partners-detail__header`, and the
    subtitle is an element with the class `academic-partners-detail__subtitle`
    below it.
*   :typoscript:`paths`, :typoscript:`templateRootPaths` and
    :typoscript:`partialRootPaths` of the page object use the key `50` instead of
    `100` on partner pages. :typoscript:`layoutRootPaths.100`, which named a
    directory that does not exist, is removed.

The setting is a site setting of the aggregate set `fgtclb/academic-partners`
and a constant for the static templates, see :ref:`partner-page-layout`. The
program page made the same move in 3.0, so the three academic page types
follow one pattern.

Impact
======

*   A partner page on a site package with a layout :file:`Default` renders
    inside it: with the header, navigation and footer of the site.
*   A site package whose layout :file:`Default` renders no section
    :html:`Main` renders the partner page without its content.
*   A site package without a layout :file:`Default` renders the partner page
    as before, without the frame of the site. A layout named by the setting
    that the site package does not have fails the page.
*   A partner page with a subtitle shows it below the title. The title is
    wrapped in an element with the class `academic-partners-detail__header`
    whether or not a subtitle is set.
*   A :typoscript:`PAGEVIEW` site package that assigns a variable
    :typoscript:`data` of its own no longer breaks partner pages. The page
    record now comes from :html:`{page}` first, in the template and in the
    data processor.
*   A path a project registered at a key between `50` and `100` now wins over
    the extension, where it lost before. A site package path at `100` is no
    longer replaced on partner pages.
*   A project that set, read or cleared :typoscript:`page.10.paths.100`,
    :typoscript:`templateRootPaths.100`, :typoscript:`partialRootPaths.100` or
    :typoscript:`layoutRootPaths.100` inside the condition on the partner page
    type reaches nothing of this extension there any more.

Affected Installations
======================

Every installation that renders partner pages on a site package with page
layouts, and every installation that styles the markup of the partner page or
changes its paths at the key `100`.

An override of the whole :file:`AcademicPartner.html` keeps rendering, without a
layout, as long as it does not render :typoscript:`styles.content.getContent`,
see :ref:`breaking-partners-content-load-set-removed`.

Migration
=========

*   A site package whose layout for partner pages has another name sets
    :typoscript:`plugin.tx_academicpartners.page.layout` to it. One whose
    layout renders another section than :html:`Main` overrides
    :file:`Pages/AcademicPartner.html`.
*   A project that overrides :file:`AcademicPartner.html` to change one part
    moves that change to the matching partial below
    :file:`Partner/Page/` and removes the template override.
*   A project that added a subtitle of its own renders the page field
    :guilabel:`Subtitle` instead, or overrides :file:`Partner/Page/Header.html`.
*   Move a path set at the key `100` inside the partner page condition to
    `50`, or to a key above it to win over the extension.
*   Adjust stylesheets that relied on the title being a direct child of the
    flex container.

..  index:: Frontend, Fluid, TypoScript, ext:academic_partners
