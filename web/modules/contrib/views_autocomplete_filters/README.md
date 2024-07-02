# Views Autocomplete Filters

## Table of contents

- Introduction
- Requirements
- Integrations
- Installation
- Configuration
- Maintainers

## Introduction

The Views Autocomplete Filters module extends views text fields filter with autocomplete functionality.
The supported filters are:

- `"combine"` - allows to search on multiple fields (core/views).
- `"search_api_fulltext"` - fulltext search (search_api).
- `"search_api_text"` - fulltext fields search (search_api).
- `"string"` - allows to search on multiple fields (core/views).

Some benefits of the Views Autocomplete Filters are:

- Quick find the desired entity by its title or unique text field
- Find all entities that shares a common string value from a field.
  Example: All nodes that are created by a specific user name.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/views_autocomplete_filters).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/views_autocomplete_filters).

## Requirements

This module requires the following module:

- Views - core module.

## Integrations
This module integrates with the following module:

- Search API - https://www.drupal.org/project/search_api.<br>
  It extend text search field filters

## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Enable the module at Administration > Extend.
2. Navigate to Administration > Structure > Views and create a View Page
   and chose desired "Display format" of **"fields"** for the desired content entities.
3. Add the field that you want to autocomplete filter, for example **"Title"**.
4. Add the same field filter and set it **"Exposed"**.
5. Be sure you select the right operator, usually "Contains" option is desired.
6. Then you should have the **"Use Autocomplete" checkbox** available.
7. After you checked this option you will abe able to see this module filter settings.
8. **You will need to select "Field with autocomplete results"**,
for the example given, chose **"Title"**.
9. Wii all those configuration set you should be able to have an
autocomplete filter available for the view created.

You could also build filters of the referenced entities fields by using View Advanced **"Relationships"** configuration.
You need to make sure you add the correct **"Relationship"** and then add the
desired field and filter of the referenced entities using this relationship.

## Maintainers

<!--- cspell:disable --->
- Tavi Toporjinschi - [vasike](https://www.drupal.org/u/vasike)
- Colan Schwartz - [colan](https://www.drupal.org/u/colan)
- Lucas Hedding - [heddn](https://www.drupal.org/u/heddn)
- Rob Loach - [RobLoach](https://www.drupal.org/u/robloach)
