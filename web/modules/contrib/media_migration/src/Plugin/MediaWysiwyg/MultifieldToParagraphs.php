<?php

namespace Drupal\media_migration\Plugin\MediaWysiwyg;

use Drupal\media_migration\MediaWysiwygPluginBase;

/**
 * Multifield → Paragraphs Media WYSIWYG plugin.
 *
 * @MediaWysiwyg(
 *   id = "multifield_to_paragraphs",
 *   label = @Translation("Multifield to Paragraphs"),
 *   entity_type_map = {
 *     "multifield" = "paragraph",
 *   },
 *   provider = "paragraphs"
 * )
 *
 * @see https://drupal.org/i/2977853
 * @see \Drupal\media_migration\MediaWysiwygInterface
 */
class MultifieldToParagraphs extends MediaWysiwygPluginBase {}
