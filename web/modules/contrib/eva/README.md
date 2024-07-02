# EVA

`EVA` is short for "Entity Views Attachment;" it provides a Views display plugin
that allows the output of a View to be attached to the content of any Drupal entity.
The body of a node or comment, the profile of a user account, or the listing page for
a Taxonomy term are all examples of entity content.

The placement of the view in the entity's content can be reordered on the
view display settings administration page for that entity, like other fields added
using the Field UI module.

In addition, the unique ID of the entity the view is attached to -- as well as
any tokens generated from that entity -- can be passed in as arguments to the
view. For example, you might make a View that displays posts with an 'Author ID'
argument, then use Eva to attach the view to the User entity type. When a user
profile is displayed, the User's ID will be passed in as the argument to the view
magically.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/eva).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/eva).

Note: EVA are attached to entities as pseudofields. This legacy mechanism is not particularly
well supported by the Field UI, meaning that some display features (default configuration, 
third-party settings) may not be available. If you want a View to act more like a "real"
field, other modules may be a better fit.

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Maintainers](#maintainers)

## Requirements

This module requires no modules outside of Drupal core. It is not useful unless `Views` is enabled.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Maintainers

- Andy Hebrank - [ahebrank](https://www.drupal.org/u/ahebrank)
- Larry Garfield - [Crell](https://www.drupal.org/u/crell)
- Jeff Eaton - [eaton](https://www.drupal.org/u/eaton)
- Merlin Axel Rutz - [geek-merlin](https://www.drupal.org/u/geek-merlin)
- Mike Kadin - [mkadin](https://www.drupal.org/u/mkadin)
- Jeff Robbins - [jjeff](https://www.drupal.org/u/jjeff)
