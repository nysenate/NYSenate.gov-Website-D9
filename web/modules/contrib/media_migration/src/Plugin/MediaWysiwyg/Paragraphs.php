<?php

namespace Drupal\media_migration\Plugin\MediaWysiwyg;

use Drupal\media_migration\MediaWysiwygPluginBase;

/**
 * Field collection and paragraphs Media WYSIWYG plugin.
 *
 * This plugin targets field collection and paragraph content entity types. For
 * being discovered by the Media WYSIWYG plugin manager, the Paragraphs module
 * should be installed on the destination site.
 *
 * @MediaWysiwyg(
 *   id = "paragraphs",
 *   label = @Translation("Paragraphs"),
 *   entity_type_map = {
 *     "field_collection_item" = "paragraph",
 *     "paragraphs_item" = "paragraph",
 *   },
 *   provider = "paragraphs"
 * )
 *
 * @see \Drupal\media_migration\MediaWysiwygInterface
 */
class Paragraphs extends MediaWysiwygPluginBase {}
