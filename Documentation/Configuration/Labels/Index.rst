..  index:: Configuration; Labels
..  _configuration-labels:

======
Labels
======

The labels this extension shows in the frontend come from
:file:`EXT:academic_partners/Resources/Private/Language/locallang.xlf` and its
translations; the table below names the ones that come from another file. A site
changes a label without copying a template, in TypoScript:
under :typoscript:`plugin.tx_academicpartners._LOCAL_LANG` for every content element
of the extension, or under :typoscript:`plugin.tx_academicpartners_<plugin>._LOCAL_LANG`
for one of them. A label set for the plugin wins over one set for the extension.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

    plugin.tx_academicpartners._LOCAL_LANG {
      default.sorting.field.label = Sort by
      de.sorting.field.label = Sortieren nach
    }

    # Only in one content element:
    plugin.tx_academicpartners_list._LOCAL_LANG.default.sorting.field.label = Sort by

The dots of a key need no escaping: TypoScript reads them as levels of its tree,
and TYPO3 joins the levels to the key again. A label of another language goes
under its language key, :typoscript:`de` for German.

..  list-table:: The path of each content element
    :header-rows: 1

    *   - Content element
        - Path
    *   - :guilabel:`Partners List` (:typoscript:`academicpartners_list`)
        - :typoscript:`plugin.tx_academicpartners_list._LOCAL_LANG`
    *   - :guilabel:`Partners Map` (:typoscript:`academicpartners_map`)
        - :typoscript:`plugin.tx_academicpartners_map._LOCAL_LANG`
    *   - :guilabel:`Partnerships List` (:typoscript:`academicpartners_partnershipslist`)
        - :typoscript:`plugin.tx_academicpartners_partnershipslist._LOCAL_LANG`
    *   - :guilabel:`Partnerships Teaser` (:typoscript:`academicpartners_partnershipsteaser`)
        - :typoscript:`plugin.tx_academicpartners_partnershipsteaser._LOCAL_LANG`

The page template of a partner page is not rendered by a plugin: a site sets
its labels under the path of the extension.

A language file override works as well, and replaces the label of the file
itself: :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`.

Earlier versions of this extension read these overrides on TYPO3 v12 and v13 from
:typoscript:`plugin.tx_academic_partners` instead, see
:ref:`the changelog <important-label-overrides-use-the-documented-path>`.

Where the labels are shown
==========================

Placeholders in angle brackets stand for a part of the key that the template
or the code fills in, a category type or a field name for example.

..  list-table::
    :header-rows: 1
    :widths: 45 55

    *   - Key
        - Shown by
    *   - :xml:`academic_partners.address`
        - :file:`Pages/AcademicPartner.html`
    *   - :xml:`filter.moreFilters`
        - :file:`Partials/Partner/DemandCategories.html`
    *   - :xml:`list.noPartnersFound`
        - :file:`Partials/Partner/ItemList.html`, :file:`Templates/Partner/Map.html`
    *   - :xml:`map.noLocatedPartnersFound`
        - :file:`Templates/Partner/Map.html`
    *   - :xml:`sorting.direction.label`
        - :file:`Partials/Partner/DemandSorting.html`
    *   - :xml:`sorting.field.label`
        - :file:`Partials/Partner/DemandSorting.html`
    *   - :xml:`sys_category.partners.<type>`
        - :file:`Pages/AcademicPartner.html`, :file:`Partials/Partner/DemandCategories.html`, :file:`Partials/Partner/Item.html`, :file:`Partials/Partnerships/List/Item.html`, :file:`Partials/Partnerships/Teaser/Item.html`
    *   - :xml:`sys_category.partners.allOptions`
        - :file:`Partials/Partner/DemandCategories.html`
    *   - :xml:`sys_category.partners.allOptions.<type>`
        - :file:`Partials/Partner/DemandCategories.html`
    *   - :xml:`sorting.field.<field>`, :xml:`sorting.direction.<direction>`
        - The options of the sorting select, translated by its view helper
    *   - :xml:`<ISO code>.name` of :file:`EXT:core/Resources/Private/Language/Iso/countries.xlf`
        - The country of a partner's address on the partner page
