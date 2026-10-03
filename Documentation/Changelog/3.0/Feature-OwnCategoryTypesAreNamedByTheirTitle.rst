.. _feature-1791035983:

====================================================
Feature: Own category types are named by their title
====================================================

Description
===========

A project may register category types of its own for the group `partners`. The
frontend named a type by the label :xml:`sys_category.partners.<type>` of this
extension, which only knows the shipped types, so such a type showed no label
unless a site added one through :typoscript:`_LOCAL_LANG`.

A type without a label is now named by the title it is registered with in
:file:`Configuration/CategoryTypes.yaml`, in the language of the page. A title
that is an :php:`LLL:` reference is translated, a literal title is shown as
written:

..  code-block:: yaml
    :caption: EXT:site_package/Configuration/CategoryTypes.yaml

    types:
      - identifier: funding_body
        title: 'LLL:EXT:site_package/Resources/Private/Language/locallang_be.xlf:funding_body'
        group: partners
        icon: 'EXT:site_package/Resources/Public/Icons/FundingBody.svg'

A label of this extension, and one a site sets under
:typoscript:`plugin.tx_academicpartners._LOCAL_LANG` or under the
path of one plugin, still wins. The shipped types keep their labels.

It applies to

*   the categories of the partner page,
    :file:`Partials/Partner/Page/Categories.html`;
*   the categories of each card of the :guilabel:`Partners List`,
    :file:`Partials/Partner/Item.html`, and of each item of the
    :guilabel:`Partnerships List` and the :guilabel:`Partnerships Teaser`,
    :file:`Partials/Partnerships/List/Item.html` and
    :file:`Partials/Partnerships/Teaser/Item.html`;
*   the filter selects of the :guilabel:`Partners List`,
    :file:`Partials/Partner/DemandCategories.html`.

See :ref:`configuration-labels`.

Impact
======

A template override of one of the partials or templates above keeps the old
lookup until it hands the label to the view helper
:html:`ct:categoryTypeTitle` of `EXT:category_types`, which renders the title
when the label is empty:

..  code-block:: html
    :caption: EXT:site_package/Resources/Private/Partials/Partner/DemandCategories.html

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:ct="http://typo3.org/ns/FGTCLB/CategoryTypes/ViewHelpers"
          data-namespace-typo3-fluid="true">

    {f:translate(key: 'sys_category.partners.{categoryKey}', extensionName: 'AcademicPartners')
        -> ct:categoryTypeTitle(group: 'partners', identifier: categoryKey)}

An empty label counts as none: a label a site blanks through
:typoscript:`_LOCAL_LANG` now falls back to the registered title. A shipped
type stays unlabelled on TYPO3 v13 instead, because its registered title is
the blanked label itself.

.. index:: Frontend, Fluid, NotScanned, ext:academic_partners
