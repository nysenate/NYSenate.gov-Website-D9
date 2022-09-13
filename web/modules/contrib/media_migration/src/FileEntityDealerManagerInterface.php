<?php

namespace Drupal\media_migration;

/**
 * Interface of FileEntityDealerManager.
 *
 * @see \Drupal\media_migration\FileEntityDealerManager
 */
interface FileEntityDealerManagerInterface {

  /**
   * Gets the plugin definitions for the specified file entity type.
   *
   * @param string $type
   *   The file entity type.
   * @param string $scheme
   *   The URI scheme.
   *
   * @return \Drupal\media_migration\FileEntityDealerPluginInterface|null
   *   A fully configured plugin instance or NULL if no applicable plugin was
   *   found.
   */
  public function createInstanceFromTypeAndScheme(string $type, string $scheme);

}
