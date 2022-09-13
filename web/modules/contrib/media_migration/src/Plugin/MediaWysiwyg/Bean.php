<?php

namespace Drupal\media_migration\Plugin\MediaWysiwyg;

use Drupal\media_migration\MediaWysiwygPluginBase;

/**
 * Media WYSIWYG plugin for bean → block_content migrations.
 *
 * @MediaWysiwyg(
 *   id = "bean",
 *   label = @Translation("Bean"),
 *   entity_type_map = {
 *     "bean" = "block_content",
 *   },
 *   provider = "bean_migrate"
 * )
 */
class Bean extends MediaWysiwygPluginBase {}
