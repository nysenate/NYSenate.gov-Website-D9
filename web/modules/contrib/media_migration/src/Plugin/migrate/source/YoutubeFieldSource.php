<?php

namespace Drupal\media_migration\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * This source plugin migrates Youtube Field.
 *
 * @MigrateSource(
 *   id = "youtube",
 *   source_module = "youtube"
 * )
 */
class YoutubeFieldSource extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_youtube_field_input' => 'URL of video',
      'field_youtube_field_video_id' => 'video ID',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'input' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    $query = NULL;
    foreach ($this->getYoutubeFieldNames() as $youtube_field_name) {
      $union_query = $this->select("field_data_$youtube_field_name", $youtube_field_name);
      $union_query->addField($youtube_field_name, "{$youtube_field_name}_input", 'input');
      static::addUnionQuery($query, $union_query);
    }
    $main_query = $this->select($query, 'all_yt')->fields('all_yt');
    $main_query->orderBy('all_yt.input');
    return $main_query;
  }

  /**
   * Fetch field names for Youtube fields.
   *
   * @return string[]
   *   array of Youtube field names.
   */
  protected function getYoutubeFieldNames(): array {
    $youtube_fields = $this->select('field_config')
      ->fields('field_config', ['field_name'])
      ->condition('type', 'youtube')
      ->condition('module', 'youtube')
      ->execute()
      ->fetchAll();
    $field_name = [];
    foreach ($youtube_fields as $youtube_field) {
      array_push($field_name, $youtube_field['field_name']);
    }
    return $field_name;
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
