<?php

namespace Drupal\filefield_paths;

use Drupal\Core\Language\Language;

/**
 * Defines a service for creating file redirects.
 */
interface RedirectInterface {

  /**
   * Creates a redirect for a moved File field.
   *
   * @param string $source
   *   The source file URL.
   * @param string $path
   *   The moved file URL.
   * @param \Drupal\Core\Language\Language $language
   *   The language of the source file.
   */
  public function createRedirect($source, $path, Language $language);

}
