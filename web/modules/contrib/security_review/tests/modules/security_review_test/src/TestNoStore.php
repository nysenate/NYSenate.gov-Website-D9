<?php

namespace Drupal\security_review_test;

/**
 * A test security check for testing extensibility.
 *
 * Same as Test, but doesn't store findings.
 */
class TestNoStore extends Test {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Test without storing findings';
  }

  /**
   * {@inheritdoc}
   */
  public function storesFindings() {
    return FALSE;
  }

}
