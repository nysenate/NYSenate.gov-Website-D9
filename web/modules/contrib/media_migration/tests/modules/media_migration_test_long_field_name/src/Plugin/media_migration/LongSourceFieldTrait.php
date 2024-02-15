<?php

namespace Drupal\media_migration_test_long_field_name\Plugin\media_migration;

use Drupal\Component\Utility\Crypt;

/**
 * Trait for creating long source field names.
 */
trait LongSourceFieldTrait {

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return self::getLongFieldName(parent::getDestinationMediaSourceFieldName());
  }

  /**
   * Generates a reproducible 45 long string from the given source.
   *
   * @param string $source_field_name
   *   The original source field name.
   *
   * @return string
   *   A 45 chars long source field name.
   */
  private static function getLongFieldName(string $source_field_name): string {
    $suffix = 'mm' . preg_replace(
        '/\W/',
        '_',
        strtolower(Crypt::hashBase64($source_field_name))
      );
    return $source_field_name . '_' . substr($suffix, 0, 44 - strlen($source_field_name));
  }

}
