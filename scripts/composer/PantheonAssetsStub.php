<?php

/**
 * @file
 * Stub for the Pantheon\Integrations\Assets class.
 *
 * This class is provided by Pantheon's platform when running on their servers.
 * For local development this stub allows settings.php to load correctly.
 * The real class is only available in the Pantheon runtime environment.
 */

namespace Pantheon\Integrations;

/**
 * Stub for Pantheon's Assets integration class.
 */
class Assets {

  /**
   * Returns the directory containing settings.pantheon.php.
   *
   * @return string
   *   Path to the sites/default directory.
   */
  public static function dir(): string {
    // The settings.pantheon.php file is scaffolded into sites/default.
    return DRUPAL_ROOT . '/sites/default';
  }

}

