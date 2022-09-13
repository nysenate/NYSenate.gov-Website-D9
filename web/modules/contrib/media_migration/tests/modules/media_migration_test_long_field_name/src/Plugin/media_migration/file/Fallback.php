<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file;

use Drupal\media_migration\Plugin\media_migration\file\Fallback as FallbackBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for fallback file dealer plugin.
 */
class Fallback extends FallbackBase {

  use LongSourceFieldTrait;

}
