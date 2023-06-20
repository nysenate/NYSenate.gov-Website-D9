<?php

namespace Drupal\nys_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Looks at a body field and transform media embed tags.
 *
 * @MigrateProcessPlugin(
 *   id = "transform_embed_media"
 * )
 */
class TransformEmbedMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (empty($value)) {
      return;
    }

    return $this->sbnMigrationsInsertLinkIntoBody($value);
  }

  /**
   * Get the inline image link info for this fid.
   *
   * Requires: fid.
   *
   * Returns an array with the FileName, UUID and RealPath for the fid image
   */
  public function sbnMigrationsGetImageLinkInfo($fid) {

    /*
     * Found via: https://drupal.stackexchange.com/questions/
     * 309092/migrating-wysiwyg-ckeditor-embedded-images-to-embeded-images
     */

    $link_info = [];

    $db = \Drupal::database();

    $query = $db->select('file_managed', 'fm');
    // Find this fid.
    $query->condition('fm.fid', $fid, '=');
    $query->fields('fm', ['uri']);
    $query->fields('fm', ['uuid']);
    $query->fields('fm', ['filename']);
    $result = $query->execute();
    foreach ($result as $record) {
      $link_info['uuid'] = $record->uuid;
      $link_info['filename'] = $record->filename;
      $uri = $record->uri;
      $link_info['path'] = \Drupal::service('file_system')->realpath($uri);
      $link_info['path'] = str_replace(' ', '%20', $link_info['path']);
    }

    return $link_info;
  }

  /**
   * Insert the correctly formatted D9 link into text body.
   *
   * Requires: body text field.
   *
   * Returns updated body text field with correct links (if necessary)
   */
  public function sbnMigrationsInsertLinkIntoBody($body) {

    $pattern = '/\[\[[^\]]+\"fid\"\:\"([0-9]+)\"\,.+\]\]/i';

    // Search for the d7 code.
    preg_match_all($pattern, $body, $embeds_found);

    if (!empty($embeds_found)) {
      foreach ($embeds_found[1] as $key => $fid) {

        // Get the link information for this $fid.
        $link_info = $this->sbnMigrationsGetImageLinkInfo($fid);
        // Only continue processing if something is returned.
        if (!empty($link_info)) {
          // Create the link.
          $link = '<img alt="' . $link_info['filename'] . '" data-entity-type="file" data-entity-uuid="' . $link_info['uuid'] . '" src="' . $link_info['path'] . '" />';
          // This is the pattern for replacing links in the body text.
          $pattern01 = '/\[\[[^\]]+\"fid\"\:\"(' . $fid . ')\"\,.+\]\]/i';
          // This will be the the new $link replacing the old code.
          $replacement = $link;
          // This is the new body text.
          $body = preg_replace($pattern01, $replacement, $body);
        }
      }
    }

    return $body;
  }

}
