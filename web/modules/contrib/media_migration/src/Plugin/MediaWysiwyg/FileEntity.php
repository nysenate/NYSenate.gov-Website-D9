<?php

namespace Drupal\media_migration\Plugin\MediaWysiwyg;

use Drupal\media_migration\MediaWysiwygPluginBase;

/**
 * Media WYSIWYG plugin for file entity → media migrations.
 *
 * @MediaWysiwyg(
 *   id = "file_entity",
 *   label = @Translation("File entity (media)"),
 *   entity_type_map = {
 *     "file_entity" = "media",
 *   }
 * )
 */
class FileEntity extends MediaWysiwygPluginBase {}
