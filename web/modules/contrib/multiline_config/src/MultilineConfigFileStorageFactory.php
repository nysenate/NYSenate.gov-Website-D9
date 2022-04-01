<?php

namespace Drupal\multiline_config;

use Drupal\Core\Site\Settings;

/**
 * Provides a custom factory for creating multiline config file storage objects.
 */
class MultilineConfigFileStorageFactory {

  /**
   * Returns an object working with the active config directory.
   *
   * @return \Drupal\multiline_config\MultilineConfigFileStorage
   *   The file storage object.
   *
   * @deprecated in Drupal 8.0.x and will be removed before 9.0.0. Drupal core
   * no longer creates an active directory.
   *
   * @throws \Exception
   */
  public static function getActive() {
    return new MultilineConfigFileStorage(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
  }

  /**
   * Returns an object working with the sync config directory.
   *
   * @return \Drupal\multiline_config\MultilineConfigFileStorage
   *   The file storage object.
   *
   * @throws \Exception
   */
  public static function getSync() {
    return new MultilineConfigFileStorage(Settings::get('config_sync_directory'));
  }

}

