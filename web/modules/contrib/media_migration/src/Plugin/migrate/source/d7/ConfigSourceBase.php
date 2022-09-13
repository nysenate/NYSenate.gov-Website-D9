<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

/**
 * Base class for media configurations.
 */
abstract class ConfigSourceBase extends DrupalSqlBaseWithCountCompatibility {

  use MediaMigrationDatabaseTrait;

  const MULTIPLE_SEPARATOR = '::';

}
