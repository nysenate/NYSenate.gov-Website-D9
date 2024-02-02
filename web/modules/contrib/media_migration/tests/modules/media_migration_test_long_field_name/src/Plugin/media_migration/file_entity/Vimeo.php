<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file_entity;

use Drupal\media_migration\Plugin\media_migration\file_entity\Vimeo as VimeoBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for vimeo file entity dealer plugin.
 */
class Vimeo extends VimeoBase {

  use LongSourceFieldTrait;

}
