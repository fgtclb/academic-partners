..  _important-1790383005:

=======================================================================
Important: Label overrides of the filters are read from the plugin path
=======================================================================

Description
===========

The filter form of the :guilabel:`Partners List` and the :guilabel:`Partners
Map` translates its labels with the extension name :html:`AcademicPartners`
instead of :html:`academic_partners`. TYPO3 v12 and v13 build the TypoScript
path of :typoscript:`_LOCAL_LANG` from that name as it is given, so they read
label overrides of the filters from :typoscript:`plugin.tx_academic_partners`.
They now read them from :typoscript:`plugin.tx_academicpartners` and
:typoscript:`plugin.tx_academicpartners_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway.

It concerns the labels the filter form renders:
:xml:`sys_category.partners.<type>`, :xml:`sys_category.partners.allOptions`,
:xml:`sys_category.partners.allOptions.<type>` and :xml:`filter.moreFilters`.

Impact
======

On TYPO3 v12 and v13, an override of one of these labels under
:typoscript:`plugin.tx_academic_partners._LOCAL_LANG` no longer reaches the
filter form. Set it under :typoscript:`plugin.tx_academicpartners._LOCAL_LANG`
as well:

..  code-block:: typoscript

    plugin.tx_academicpartners._LOCAL_LANG.default.sys_category.partners.allOptions = All
    plugin.tx_academicpartners._LOCAL_LANG.de.sys_category.partners.allOptions = Alle

Copy an override of a type label, :xml:`sys_category.partners.<type>`, rather
than move it: the partner items of the list, the partnerships and the partner
page render the same label and still read it from the old path on TYPO3 v12 and
v13.

Other templates of the extension are not affected by this change.

..  index:: Frontend, Fluid, TypoScript, ext:academic_partners
