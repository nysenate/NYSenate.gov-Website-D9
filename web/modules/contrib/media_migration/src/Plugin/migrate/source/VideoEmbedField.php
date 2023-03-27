<?php

namespace Drupal\media_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * This source plugin migrates Video Embed Field.
 *
 * @MigrateSource(
 *   id = "video_embed",
 *   source_module = "video_embed_field"
 * )
 */
class VideoEmbedField extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'video_url' => 'URL of video',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'video_url' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = NULL;
    foreach ($this->getVideoEmbedFieldNames() as $video_embed_field_name) {
      $union_query = $this->select("field_data_$video_embed_field_name", $video_embed_field_name);
      $union_query->addField($video_embed_field_name, "{$video_embed_field_name}_video_url", 'video_url');
      static::addUnionQuery($query, $union_query);
    }
    $main_query = $this->select($query, 'all_embed_video')->fields('all_embed_video');
    $main_query->orderBy('all_embed_video.video_url');
    return $main_query;
  }

  /**
   * Fetch field names for  Video Embed fields.
   *
   * @return string[]
   *   array of Video Embed field names.
   */
  protected function getVideoEmbedFieldNames(): array {
    $video_embed_fields = $this->select('field_config')
      ->fields('field_config', ['field_name'])
      ->condition('type', 'video_embed_field')
      ->condition('module', 'video_embed_field')
      ->execute()
      ->fetchAllKeyed(0, 0);

    return array_values($video_embed_fields);
  }

  /**
   * Performs a query union.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface|null $destination
   *   The "destination" query which should be extended.
   * @param \Drupal\Core\Database\Query\SelectInterface $source
   *   The query which should be added.
   */
  protected static function addUnionQuery(&$destination, SelectInterface $source) {
    if ($destination instanceof SelectInterface) {
      $destination->union($source);
      return;
    }
    $destination = clone $source;
  }

}
