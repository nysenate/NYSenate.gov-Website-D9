<?php

namespace Drupal\Tests\node_revision_delete\Traits;

/**
 * Trait for common test functions.
 */
trait NodeRevisionDeleteTestTrait {

  /**
   * Returns an array of content types to track.
   *
   * Each index determine a set of content types.
   *
   * @return array
   *   An array of content types to track keyed by content type machine name.
   */
  public function getNodeRevisionDeleteTrackArray() {

    $values = [
      'article' => [
        'node_revision_delete' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 8,
          'when_to_delete' => 12,
        ],
      ],
      'blog' => [
        'node_revision_delete' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 10,
        ],
      ],
      'page' => [
        'node_revision_delete' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 6,
          'when_to_delete' => 8,
        ],
      ],
    ];

    return $values;
  }

}
