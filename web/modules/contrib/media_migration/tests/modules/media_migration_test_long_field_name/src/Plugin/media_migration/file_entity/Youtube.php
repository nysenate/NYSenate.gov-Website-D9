<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file_entity;

use Drupal\media_migration\Plugin\media_migration\file_entity\Youtube as YoutubeBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for youtube file entity dealer plugin.
 */
class Youtube extends YoutubeBase {

  use LongSourceFieldTrait;

}
