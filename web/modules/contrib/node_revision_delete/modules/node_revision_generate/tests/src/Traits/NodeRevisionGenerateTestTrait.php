<?php

namespace Drupal\Tests\node_revision_generate\Traits;

/**
 * Trait for common test functions.
 */
trait NodeRevisionGenerateTestTrait {

  /**
   * Returns the revisions age array.
   *
   * @return array
   *   The revisions age.
   */
  public function getRevisionAge() {
    return [
      86400,
      604800,
      2592000,
      86400,
      604800,
    ];
  }

}
