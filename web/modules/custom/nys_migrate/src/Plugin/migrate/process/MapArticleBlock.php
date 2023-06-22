<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Fetches a block based on a paragraph id and type.
 *
 * @MigrateProcessPlugin(
 *   id = "map_article_block"
 * )
 */
class MapArticleBlock extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return;
    }

    // Set up an empty array for return.
    $blocks = [];

    // DB connections can be reused through all queries.
    // Establish a connection to the migration db.
    $nys7_db = Database::getConnection('default', 'migrate');

    // Establish a connection to the current db.
    $db = \Drupal::database();

    // Query for the original Paragraph type value.
    $d7_paragraph_type = $nys7_db->select('paragraphs_item', 'p')
      ->fields('p', ['bundle'])
      ->condition('item_id', $value['value'])
      ->execute()->fetchCol();

    // If a type is returned, find the appropriate migration mapping.
    // Then find the block we are looking for.
    if ($d7_paragraph_type) {

      $lookup_tb = FALSE;

      // Switch through paragraph types to find map table.
      switch ($d7_paragraph_type[0]) {
        case 'pg_accordion':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_accordion';
          break;

        case 'pg_featured_bills':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_featured_bills';
          break;

        case 'pg_file_uploads':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_file_uploads';
          break;

        case 'feature_image':
          $lookup_tb = 'migrate_map_nys_paragraphs_feature_image';
          break;

        case 'pg_image_slider':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_image_slider';
          break;

        case 'pg_text':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_text';
          break;

        case 'pg_video':
          $lookup_tb = 'migrate_map_nys_paragraphs_pg_video';
          break;

      }

      if ($lookup_tb) {

        // Grab Destination ID for the corresponding block.
        $dest_block = $db->select($lookup_tb, 'm')
          ->fields('m', ['destid1'])
          ->condition('sourceid1', $value['value'])
          ->execute()->fetchCol();

        if (!$dest_block) {
          throw new MigrateSkipRowException(sprintf('Mapping id for Paragraph %s not found', $value['value']));
        }
        else {

          // If we have a dest id, then we need to fetch revision ID.
          // Retrieve the Revision ID.
          $result = $db->select('block_content', 'bc')
            ->fields('bc', ['revision_id'])
            ->condition('id', $dest_block[0])
            ->execute()->fetchCol();

          if (!$result) {
            throw new MigrateSkipRowException(sprintf('Rev ID for block %s not found', $value['value']));
          }

          $blocks = [
            'target_id' => $dest_block[0],
            'target_revision_id' => $result[0],
          ];
        }
      }
    }

    return $blocks;
  }

}
