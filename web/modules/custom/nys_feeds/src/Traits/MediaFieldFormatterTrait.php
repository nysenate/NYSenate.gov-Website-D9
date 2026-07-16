<?php

namespace Drupal\nys_feeds\Traits;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Exposes and formats a variety of media-related fields.
 */
trait MediaFieldFormatterTrait {

  /**
   * Gets the standard media fields, if available.
   */
  protected function getMediaFields(ContentEntityInterface $entity): array {
    $ret = [];
    $fields = [
      'teleconference_id' => 'field_teleconference_id_number',
      'teleconference_number' => 'field_teleconference_number',
      'ustream_id' => 'field_ustream',
      'video_redirect' => 'field_video_redirect',
      'video_status' => 'field_video_status',
      'yt_archive_id' => 'field_yt',
    ];
    foreach ($fields as $key => $val) {
      try {
        $field = $entity->get($val);
      }
      catch (\Exception) {
        $field = NULL;
      }
      if ($field) {
        $ret[$key] = $val->value ?? '';
      }
    }
    return $ret;
  }

}
