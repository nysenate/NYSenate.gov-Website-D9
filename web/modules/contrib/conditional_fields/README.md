CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Conditional Fields allows you to manage sets of dependencies between fields. When a field is “dependent”, it will only be available for editing and displayed if the state of the “dependee” field matches the right condition. When editing a node (or any other entity type that supports fields, like users and categories), the dependent fields are dynamically modified with the States API.

A simple use case would be defining a custom “Article teaser" field that is shown only if a "Has teaser" checkbox is checked, but much more complex options are available.

  # Working conditions
  - [x] is filled/empty
  - [ ] is touched/untouched
  - [ ] is focused/unfocused
  - [x] is checked/unchecked
  - has value (in progress)
    - [ ] Values input mode: widget (see below)
    - [x] Values input mode: regular expression
    - [x] Values input mode: set of values (AND)
    - [x] Values input mode: set of values (OR)
    - [x] Values input mode: set of values (XOR)
    - [x] Values input mode: set of values (NOT)

  # Values input mode: widget
  Support all core widgets https://www.drupal.org/node/2875774
  - [ ] datetime_timestamp
  - [x] boolean_checkbox
  - [ ] email_default https://www.drupal.org/node/2875776
  - [ ] entity_reference_autocomplete_tags https://www.drupal.org/node/2875779
  - [x] entity_reference_autocomplete
  - [x] language_select https://www.drupal.org/node/2875782
  - [ ] number
  - [x] options_buttons
  - [x] options_select https://www.drupal.org/node/2867717
  - [ ] string_textarea
  - [x] string_textfield
  - [ ] uri https://www.drupal.org/node/2875784
  - [ ] comment_default
  - [ ] moderation_state_default
  - [ ] datetime_datelist
  - [x] datetime_default https://www.drupal.org/node/2867754
  - [ ] daterange_datelist
  - [ ] daterange_default
  - [ ] file_generic
  - [ ] image_image
  - [x] link_default https://www.drupal.org/node/2867752
  - [ ] path
  - [ ] telephone_default
  - [x] text_textarea https://www.drupal.org/node/2875774
  - [ ] text_textarea_with_summary https://www.drupal.org/node/2875774
  - [ ] text_textfield https://www.drupal.org/node/2875774

  # Working states
  - [x] visible/invisible
  - [x] required/optional
  - [ ] filled with a value/emptied
  - [ ] enabled/disabled
  - [x] checked/unchecked
  - [ ] expanded/collapsed

  # Known issues
  - [ ] XOR: saving data from hidden field if it was filled. (discuss it)
  - [ ] filter available fields for creating conditions
  - [ ] Add permissions check

  # Enhancement
  - [ ] User roles dependency (edit context)
  - [ ] User roles dependency (view context)
  - [ ] View context


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
------------

After enable the module, you can create conditional fields on /admin/structure/conditional_fields.


MAINTAINERS
-----------

Current maintainers:
 * Colan Schwartz (colan) - https://www.drupal.org/u/colan
 * Christopher Gervais (ergonlogic) - https://www.drupal.org/u/ergonlogic
 * Merlin Axel Rutz (geek-merlin) - https://www.drupal.org/u/geek-merlin
 * Olga Rabodzei (olgarabodzei) - https://www.drupal.org/u/olgarabodzei
 * Thalles Ferreira (thalles) - https://www.drupal.org/u/thalles
