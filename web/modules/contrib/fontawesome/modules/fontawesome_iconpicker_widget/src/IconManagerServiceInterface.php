<?php

namespace Drupal\fontawesome_iconpicker_widget;

/**
 * Provides an interface for icon manager.
 */
interface IconManagerServiceInterface {

  /**
   * Get formatted icon list.
   *
   * @return array
   *   An array of icons.
   */
  public function getFormattedIconList();

  /**
   * Get formatted term list.
   *
   * @return array
   *   An array of search terms.
   */
  public function getFormattedTermList();

  /**
   * Get icon base name from class.
   *
   * @param string $class
   *   The icon class name.
   *
   * @return string
   *   The icon base name.
   */
  public function getIconBaseNameFromClass($class);

  /**
   * Get icon prefix from class.
   *
   * @param string $class
   *   The icon class name.
   *
   * @return string
   *   The icon style prefix.
   */
  public function getIconPrefixFromClass($class);

}
