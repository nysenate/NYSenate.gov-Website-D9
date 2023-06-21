<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks up the Revision ID for a given block_content id.
 *
 * @MigrateProcessPlugin(
 *   id = "get_block_rev_id"
 * )
 */
class GetBlockRevId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = trim($value);
    if (empty($value)) {
      return;
    }

    // Retrieve the Revision ID.
    $db = \Drupal::database();
    $result = $db->select('block_content', 'bc')
      ->fields('bc', ['revision_id'])
      ->condition('id', $value)
      ->execute()->fetchCol();

    if (!$result) {
      throw new MigrateSkipRowException(sprintf('Rev ID for block %s not found', $value));
    }

    return reset($result);
  }

}
