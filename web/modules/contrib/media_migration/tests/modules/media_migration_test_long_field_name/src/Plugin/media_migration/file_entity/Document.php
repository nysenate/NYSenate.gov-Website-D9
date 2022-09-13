<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration\file_entity;

use Drupal\media_migration\Plugin\media_migration\file_entity\Document as DocumentBase;
use Drupal\media_migration_test_long_field_name\Plugin\media_migration\LongSourceFieldTrait;

/**
 * Replacement for document file entity dealer plugin.
 */
class Document extends DocumentBase {

  use LongSourceFieldTrait;

}
