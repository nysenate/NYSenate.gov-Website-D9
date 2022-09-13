<?php

namespace Drupal\media_migration\Plugin\MediaWysiwyg;

use Drupal\media_migration\MediaWysiwygPluginBase;

/**
 * General Media WYSIWYG plugin.
 *
 * This plugin targets content entity types in Drupal core whose source entity
 * type IDs remains the same on the destination.
 *
 * @MediaWysiwyg(
 *   id = "general",
 *   label = @Translation("General"),
 *   entity_type_map = {
 *     "comment" = "comment",
 *     "node" = "node",
 *     "taxonomy_term" = "taxonomy_term",
 *     "user" = "user",
 *   },
 * )
 *
 * @see \Drupal\media_migration\MediaWysiwygInterface
 */
class General extends MediaWysiwygPluginBase {}
