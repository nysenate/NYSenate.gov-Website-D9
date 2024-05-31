<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file;

use Drupal\media_migration\Plugin\media_migration\file\Image as ImageBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for image file dealer plugin.
 */
class Image extends ImageBase {

  use LongSourceFieldTrait;

}
