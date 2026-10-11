..  _important-1790383005:

=======================================================================
Important: Label overrides of the filters are read from the plugin path
=======================================================================

Description
===========

The filter form of the :guilabel:`Partners List` and the :guilabel:`Partners
Map` translated its labels with the extension key :html:`academic_partners` as
the extension name. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides of the filters from :typoscript:`plugin.tx_academic_partners`. The
form now uses the extension name :html:`AcademicPartners`, so the overrides are
read from :typoscript:`plugin.tx_academicpartners` and
:typoscript:`plugin.tx_academicpartners_<plugin>`, the paths the TYPO3
documentation names.

It concerns the labels the filter form renders:
:xml:`sys_category.partners.<type>`, :xml:`sys_category.partners.allOptions`,
:xml:`sys_category.partners.allOptions.<type>` and :xml:`filter.moreFilters`.

Impact
======

On TYPO3 v12 and v13, an override of one of these labels under
:typoscript:`plugin.tx_academic_partners._LOCAL_LANG` no longer reaches the
filter form. Move it to
:typoscript:`plugin.tx_academicpartners._LOCAL_LANG`:

..  code-block:: typoscript

    plugin.tx_academicpartners._LOCAL_LANG.default.sys_category.partners.allOptions = All
    plugin.tx_academicpartners._LOCAL_LANG.de.sys_category.partners.allOptions = Alle

The other templates of the extension read their label overrides from the same
path, see :ref:`important-label-overrides-use-the-documented-path`. An override
of a type label, which they render too, therefore moves as a whole.

..  index:: Frontend, Fluid, TypoScript, ext:academic_partners
