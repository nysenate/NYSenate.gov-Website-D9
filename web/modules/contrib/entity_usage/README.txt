Entity Usage
============

This module provides a tool to track entity relationships in Drupal.

Currently the following tracking methods are supported:

- Entities related trough entity_reference fields
- Entities related trough link fields
- Standard HTML links inside text fields (when pointing to an entity URL).
- Entities embedded into text fields using the Entity Embed module
- Entities embedded into text fields using the LinkIt module
- Entities related through block_field fields (provided by the Block Field
  module)
- Entities related through entity_reference_revision fields
- Entities related through dynamic_entity_reference fields
- Entities related through Layout Builder. Supported methods: Core's inline
(non-reusable) content blocks, and entities selected through the contributed
Entity Browser Block module.

How it works
============

A relationship between two entities is considered so when a source entity
points to a target entity through one of the methods described above.

You can configure what entity types should be tracked when source, and what
entity types should be tracked as target. By default all content entities
(except files and users) are tracked as source.

You can also configure what plugins (from the tracking methods indicated above)
should be active. By default all plugins are active.

When a source entity is created / updated / deleted, all active plugins are
called to register potential relationships.

Content entities can have a local task link (Tab) on its canonical page linking
to a "Usage" information page, where users can see where that entity is being
used. You can configure which entity types should have a local task displaying
usage information.

In order to configure these and other settings, navigate to "Configuration ->
Content Authoring -> Entity Usage Settings" (or go to the URL
/admin/config/entity-usage/settings).

You can also display usage information in Views, or retrieve them in custom
code. Please refer to the online handbook to learn more.

Batch update
============

The module provides a tool to erase and regenerate all tracked information about
usage of entities on your site.
Go to the URL /admin/config/entity-usage/batch-update in order to start the
batch operation.

Project page and Online handbook
================================

More information can be found on the project page:
  https://www.drupal.org/project/entity_usage
and on the online handbook:
  https://www.drupal.org/docs/8/modules/entity-usage
