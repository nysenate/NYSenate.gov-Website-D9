<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field formatter settings for file fields migrated to media ones.
 *
 * @MigrateProcessPlugin(
 *   id = "file_entity_field_formatter_settings",
 *   handle_multiples = TRUE
 * )
 */
class FileEntityFieldFormatterSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ('file_entity' == $row->getSourceProperty('type')) {
      $formatter = $row->getSourceProperty('formatter');
      if (empty($formatter['settings'])) {
        $value = [];
      }
      elseif ('file_image_picture' == $formatter['type']) {
        $value['image_link'] = $formatter['settings']['image_link'];
        $value['responsive_image_style'] = $formatter['settings']['picture_mapping'];
      }
      elseif ('file_image_image' == $formatter['type']) {
        $value['image_link'] = $formatter['settings']['image_link'];
        $value['image_style'] = $formatter['settings']['image_style'];
      }
      elseif ('file_rendered' == $formatter['type']) {
        $value['view_mode'] = $formatter['settings']['file_view_mode'];
        $value['link'] = FALSE;
      }
    }
    return $value;
  }

}
