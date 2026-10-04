..  _important-partners-category-icons-come-from-the-frontend-icon-registry:

===================================================================
Important: Category type icons come from the frontend icon registry
===================================================================

Description
===========

The partner page, the partner card of the partner list, the partnerships list
and the partnerships teaser show the icon of each category type of a partner.
The partials :file:`Partials/Partner/Page/Categories.html`,
:file:`Partials/Partner/Item.html`, :file:`Partials/Partnerships/List/Item.html`
and :file:`Partials/Partnerships/Teaser/Item.html` rendered it with
``core:icon``, from the icon registry of the TYPO3 backend. They now render it
with the ``ab:icon`` ViewHelper of :guilabel:`academic_base`, from its frontend
icon registry, with the arguments they had.

The identifiers stay ``category_types.partners.<type>``, and so does the
rendered markup, the inlined drawing in its wrapper
:html:`<span class="t3js-icon icon" data-identifier="…">`. No icon of this
extension changes its registration: :guilabel:`category_types` registers every
category type icon in both registries, so the backend keeps showing the same
icons. One case changes on purpose: a category type declared without an icon
file made the partials fail with exception 1440754980 of the bitmap icon
provider of TYPO3, which the backend icon registry picks for an empty source.
It now shows TYPO3's not-found placeholder.

What the frontend shows can now differ from the backend:

*   A type that declares a ``frontendIcon`` in
    :file:`Configuration/CategoryTypes.yaml` shows that file in the frontend
    and its ``icon`` in the backend.
*   A site package replaces the icon of a type for the frontend only by
    registering its identifier, for example
    ``category_types.partners.region``, in its own
    :file:`Configuration/FrontendIcons.php`.

An override of one of the partials that still renders the icons with
``core:icon`` keeps working and keeps showing the backend icon of each type. It
misses a ``frontendIcon`` and a frontend replacement until it switches: replace
``<core:icon`` with ``<ab:icon``, keep every argument, and declare
``xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`` in the
:html:`<html>` tag of the partial.

..  index:: Fluid, Frontend, ext:academic_partners
