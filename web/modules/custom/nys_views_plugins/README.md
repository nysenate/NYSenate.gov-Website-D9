# NYS Views Plugins

This Drupal 10 module provides custom Views plugins for the NYS website.

## Features

- Adds a custom relationship plugin for reverse entity_reference_revisions relationships
- Allows block_content entities to be related to their parent nodes via the field_block field

## Usage

1. Enable the module.
2. In Views, when editing a view with block_content as the base table, add a relationship.
3. You should see a new relationship option "Parent Node (via entity_reference_revisions)".
4. Add this relationship to access fields from the parent node that references the block.

## Technical Details

This module addresses an issue where the standard entity reverse relationship doesn't work with entity_reference_revisions fields. The custom relationship plugin properly handles the join between block_content entities and their parent nodes through the entity_reference_revisions field_block.
