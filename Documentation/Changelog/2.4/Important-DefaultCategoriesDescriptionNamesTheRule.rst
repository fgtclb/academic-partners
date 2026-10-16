..  _important-partner-default-categories-description:

=================================================================
Important: The description of Default categories names their rule
=================================================================

Description
===========

The description below :guilabel:`Default categories` of the partner list and
the partner map said that selected categories of the same category type are
alternatives (OR), and that the subcategories of a selected category are added
automatically. It was taken over from the program list, and neither held for
partners.

The description now says what the list and the map do, in English and in
German: a partner is shown only when its page carries every selected category,
also where two of them are of the same category type. The subcategories of a
selected category are not included.

Impact
======

Only the text changed, the list and the map show the same partners as before.
The label key :xml:`flexform.categories.description` is unchanged, so an
override of it in a language file keeps its own text.

Affected Installations
======================

Every installation with a partner list or a partner map that has default
categories. An element with two default categories of one category type shows
only the partners that carry both. Where either of them was meant, give the
partner pages of both categories one category in common and select that one
instead.

..  index:: Backend, FlexForm, ext:academic_partners
