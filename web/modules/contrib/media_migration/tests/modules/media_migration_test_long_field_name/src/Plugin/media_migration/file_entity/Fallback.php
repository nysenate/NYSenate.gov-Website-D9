<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file_entity;

use Drupal\media_migration\Plugin\media_migration\file_entity\Fallback as FallbackBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for fallback file entity dealer plugin.
 */
class Fallback extends FallbackBase {

  use LongSourceFieldTrait;

}
