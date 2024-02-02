<?php

namespace Drupal\media_migration;

/**
 * Interface of FileDealerManager.
 *
 * @see \Drupal\media_migration\FileDealerManager
 */
interface FileDealerManagerInterface {

  /**
   * Returns a FileDealer plugin instance that matches the scheme and MIME.
   *
   * FileDealer plugins may specify scheme ("public", "private" etc) and MIMEs
   * ("image", "audio" etc). This method returns a plugin that is able to manage
   * the migration of files with the given storage scheme and main MIME type:
   *  - For first, the manager tries to find a plugin that exactly matches the
   *    given scheme and MIME.
   *  - When no plugin was found that strictly matches, then the manager tries
   *    find a plugin that's scheme isn't limited, but the given MIME matches.
   *  - When no plugin was found that matches to the given MIME, then this
   *    method tries to return a plugin instance whose MIME isn't limited, but
   *    its specified scheme matches the scheme argument.
   *  - When no matching plugin was found, but the default "fallback" plugin is
   *    available, then a fallback plugin instance will be returned.
   *
   * @param string $scheme
   *   The URI scheme.
   * @param string $mime
   *   The main MIME type's first part.
   *
   * @return \Drupal\media_migration\FileDealerPluginInterface|null
   *   A fully configured plugin instance or NULL if no applicable plugin was
   *   found.
   */
  public function createInstanceFromSchemeAndMime(string $scheme, string $mime);

}
