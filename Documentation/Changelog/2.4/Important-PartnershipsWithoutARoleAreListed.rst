..  _important-ace-917-academic-partners:

=====================================================
Important: Partnerships without a role are listed too
=====================================================

Description
===========

The partnerships list and the partnerships teaser render the partnerships of
the page they are stored on. As soon as one partnership of the page had a
role, both grouped the partnerships by role, one heading per role, and every
partnership without a role was left out of the output. Only a page whose
partnerships had no role at all listed them, in one list without headings.

The partnerships without a role are now rendered after the role groups, in
the order arranged on the page, in a list of their own without a heading.
Both content elements pass them to their template as the new variable
:html:`{partnershipsWithoutRole}`. The data processor that hands
:html:`{partnerships}` and :html:`{partnershipRoles}` to the template of a page
passes it as well.

A partnership whose role is hidden or deleted is listed the same way. Such a
role does not resolve in the frontend, so the partnership has no role there.
Before, it was left out as soon as another role of the page was visible.

The role groups are unchanged, in every language, see
:ref:`important-ace-430-academic-partners`.

Impact
======

A page that mixes partnerships with and without a role shows all of its
partners. Pages whose partnerships all have a role, and pages whose
partnerships have none, render as before. Behaviour is identical on every
supported TYPO3 version.

A site that hid a role to hide the partnerships of that role shows them again,
after the role groups. Hide the partnerships themselves instead: a hidden
partnership stays out of the output, whatever its role.

An installation that overrides :file:`Templates/Partner/PartnershipsList.html`
or :file:`Templates/Partner/PartnershipsTeaser.html` keeps its own output and
still leaves those partnerships out. Render :html:`{partnershipsWithoutRole}`
after the role groups to show them, in the markup the override gives a role
group but without the heading of the role. An override of the teaser renders
the partial :file:`Partnerships/Teaser/Item` in place of the one of the list:

..  code-block:: html

    <f:if condition="{partnershipsWithoutRole}">
        <f:for each="{partnershipsWithoutRole}" as="partnership">
            <f:render partial="Partnerships/List/Item" arguments="{
                partner: partnership.partner,
                data: data,
                settings: settings
            }" />
        </f:for>
    </f:if>

The item partials are unchanged. A partnership without a role is rendered
without the argument :html:`grouped`, so the name of its partner takes the
heading level it has in the list of a page without roles.

Affected Installations
======================

Installations with pages of which some partnerships have a role and some do
not or have a hidden or deleted role, and site packages that override the
templates of the partnerships list or teaser.

..  index:: Frontend, Fluid, ext:academic_partners
