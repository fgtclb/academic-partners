..  _integrator:

===============
For integrators
===============

:guilabel:`academic_partners` ships four content elements. This chapter has one
page for each of them: what it renders, which situation it is meant for, and
every option an editor sets on the content element. What a site configures once
for all of them, its site settings and TypoScript, is described in
:ref:`configuration`.

All four content elements are driven by one Extbase plugin and read their
options from :typoscript:`plugin.tx_academicpartners.settings`. The values an
editor stores in a content element are merged over the TypoScript of the
plugin, and a stored value replaces the TypoScript value of the same name, an
empty one included. This extension lists no option in
:typoscript:`ignoreFlexFormSettingsIfEmpty`, and its TypoScript sets no value
for any of the options on these pages, so the default of an option is the
default of the content element form. A content element saved before an option
existed stores no value for it, and the description of the option says what the
content element does then.

..  list-table::
    :header-rows: 1
    :widths: 10 25 25 40

    *   -   Icon
        -   Content element
        -   CType
        -   Shows
    *   -   ..  image:: /Images/Icons/list.svg
                :width: 32px
                :alt: Icon of the Partner List content element
        -   :ref:`Partner List <integrator-partner-list>`
        -   ``academicpartners_list``
        -   The partners of one or more storage folders as cards, with a form
            to sort and filter them and an optional pagination.
    *   -   ..  image:: /Images/Icons/map.svg
                :width: 32px
                :alt: Icon of the Partner Map content element
        -   :ref:`Partner Map <integrator-partner-map>`
        -   ``academicpartners_map``
        -   The same partners as markers on a map, with the same form.
    *   -   ..  image:: /Images/Icons/partners.svg
                :width: 32px
                :alt: Icon of the Partners Linked content element
        -   :ref:`Partners Linked <integrator-partnerships-list>`
        -   ``academicpartners_partnershipslist``
        -   The partners an editor linked to the page the content element is
            on, grouped by the role of the partnership.
    *   -   ..  image:: /Images/Icons/partnerships-teaser.svg
                :width: 32px
                :alt: Icon of the Partner Logo Teaser content element
        -   :ref:`Partner Logo Teaser <integrator-partnerships-teaser>`
        -   ``academicpartners_partnershipsteaser``
        -   The same partners as :guilabel:`Partners Linked`, in an element of
            its own that a site package styles as a strip of logos.

The partners themselves are pages of the page type :guilabel:`Academic partner`.
The list and the map find them in the storage folders an editor chooses, the two
partnership elements through the partnerships of their page.

..  toctree::
    :hidden:
    :titlesonly:

    PartnerList
    PartnerMap
    PartnershipsList
    PartnershipsTeaser
