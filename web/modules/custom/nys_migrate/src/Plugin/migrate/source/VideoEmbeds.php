<?php

namespace Drupal\nys_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * The 'video_embed' source plugin.
 *
 * @MigrateSource(
 *   id = "video_embeds",
 *   source_module = "nys_migrate"
 * )
 */
class VideoEmbeds extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $content_types = [
      'event',
      'meeting',
      'public_hearing',
      'session',
      'video',
    ];
    $query = $this->select('field_data_field_yt', 'f');
    $query->join('node', 'n', 'f.entity_id = n.nid');
    $query->fields(
          'f', [
            'entity_id',
            'field_yt_video_url',
          ]
      )
      ->fields(
              'n', [
                'title',
                'created',
                'changed',
                'uid',
              ]
          )
      ->condition('bundle', $content_types, 'IN');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_id' => $this->t('Entity ID'),
      'field_yt_video_url' => $this->t('Video URL'),
      'title' => $this->t('Title'),
      'created' => $this->t('Created Date'),
      'changed' => $this->t('Changed Date'),
      'uid' => $this->t('User ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

}
