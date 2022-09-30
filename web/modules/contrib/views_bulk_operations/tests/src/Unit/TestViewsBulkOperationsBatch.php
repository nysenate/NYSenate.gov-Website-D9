<?php

namespace Drupal\Tests\views_bulk_operations\Unit;

use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;

/**
 * Override some class methods for proper testing.
 */
class TestViewsBulkOperationsBatch extends ViewsBulkOperationsBatch {

  /**
   * Override t method.
   */
  public static function translate($string, array $args = [], array $options = []): string {
    return \strtr($string, $args);
  }

  /**
   * Override message method.
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE): ?string {
    static $storage;
    if ($message === NULL) {
      $output = $storage;
      $storage = NULL;
      return $output;
    }
    else {
      if (isset($storage)) {
        $storage .= ' | ' . (string) $message;
      }
      else {
        $storage = (string) $message;
      }
    }
    return NULL;
  }

}
