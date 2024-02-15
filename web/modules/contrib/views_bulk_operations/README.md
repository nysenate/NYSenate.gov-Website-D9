# Views Bulk Operations

Views Bulk Operations augments Views by allowing actions (provided by Drupal
core or contrib modules) to be executed on the selected view rows.

It does so by showing a checkbox in front of each displayed row, and adding a
select box on top of the View containing operations that can be applied.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/views_bulk_operations).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/views_bulk_operations).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
  - Getting started
  - Creating custom actions
- Additional notes
- FAQ
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

### Getting started

1. Create a View with a page or block display.
1. Add a "Views bulk operations" field (global), available on
   all entity types.
1. Configure the field by selecting at least one operation.
1. Go to the View page. VBO functionality should be present.


### Creating custom actions

Example that covers different possibilities is available in
modules/views_bulk_operations_example/.

In a module, create an action plugin (check the included example module,
test actions in /tests/views_bulk_operations_test/src/Plugin/Action
or \core\modules\node\src\Plugin\Action namespace for simple implementations).

Available annotation parameters:
  - id: The action ID (required),
  - label: Action label (required),
  - type: Entity type for the action, if left empty, action will be
    applicable to all entity types (required),
  - confirm: If set to TRUE and the next parameter is empty,
    the module default confirmation form will be used (default: FALSE),
  - confirm_form_route_name: Route name of the action confirmation form.
    If left empty and the previous parameter is empty, there will be
    no confirmation step (default: empty string).
  - requirements: an array of requirements an action must meet
    to be displayed on the action selection form. At the moment
    two possible requirements are supported:
    - '_permission', if the current user has that permission, the action
      execution will be possible.
    - '_custom_access', checks if the output of `customAccess` method from
      the action class is TRUE (default implementation included in
      ViewsBulkOperationsActionBase).


## Additional notes

Full documentation with examples is available at
[documentation page](https://www.drupal.org/docs/8/modules/views-bulk-operations-vbo).


## Maintainers

- Marcin Grabias - [Graber](https://www.drupal.org/u/graber)
- Bojan Živanović - [bojanz](https://www.drupal.org/u/bojanz)
- Joël Pittet - [joelpittet](https://www.drupal.org/u/joelpittet)
