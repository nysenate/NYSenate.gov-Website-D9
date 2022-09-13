<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file_entity;

use Drupal\media_migration\Plugin\media_migration\file_entity\Audio as AudioBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for audio file entity dealer plugin.
 */
class Audio extends AudioBase {

  use LongSourceFieldTrait;

}
