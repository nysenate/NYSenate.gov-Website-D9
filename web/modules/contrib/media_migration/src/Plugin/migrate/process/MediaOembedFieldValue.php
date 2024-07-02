<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Component\Utility\Variable;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes and returns oembed media field values.
 *
 * @MigrateProcessPlugin(
 *   id = "media_oembed_field_value"
 * )
 */
class MediaOembedFieldValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new \UnexpectedValueException('The value to be transformed must be a string.');
    }
    if (!preg_match('/^oembed:\/\/(.+)$/', $value, $match)) {
      throw new \UnexpectedValueException(
        sprintf(
          "The actual value doesn't seem to be an oembed URI: %s",
          Variable::export($value)
        )
      );
    }

    return urldecode($match[1]);
  }

}
