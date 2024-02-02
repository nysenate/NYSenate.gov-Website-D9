<?php

namespace Drupal\fontawesome;

/**
 * Icon Manager Service Inteface for Font Awesome.
 */
interface FontAwesomeManagerInterface {

  /**
   * Get icons.
   *
   * @return array
   *   List of all icons.
   */
  public function getIcons();

  /**
   * Extract metadata for a specific icon.
   *
   * @param string $findIcon
   *   The icon for which we want metadata.
   *
   * @return array|bool
   *   Array containing icons.
   */
  public function getIconMetadata($findIcon);

  /**
   * Returns the FontAwesome metadata file path.
   *
   * @return string
   *   The filepath of the metadata file.
   */
  public function getMetadataFilepath();

  /**
   * Loads the Font Awesome metadata.
   *
   * @return array
   *   The available FontAwesome icon metadata.
   */
  public function getMetadata();

  /**
   * Helper function returns the prefix for an icon based on icon type.
   *
   * @param array $styles
   *   An array of valid styles for the icon.
   * @param string $default
   *   The value to assign here if it's not a brand icon.
   *
   * @return string
   *   A valid prefix for this icon.
   */
  public function determinePrefix(array $styles, $default = 'fas');

}
