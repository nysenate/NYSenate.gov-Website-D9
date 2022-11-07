<?php

namespace Drupal\fontawesome_iconpicker_widget;

use Drupal\fontawesome\FontAwesomeManagerInterface;

/**
 * Icon Manager Service for Font Awesome.
 */
class IconManagerService implements IconManagerServiceInterface {

  /**
   * Drupal\fontawesome\FontAwesomeManagerInterface definition.
   *
   * @var \Drupal\fontawesome\FontAwesomeManagerInterface
   */
  protected $fontAwesomeManager;

  /**
   * Constructs a new IconManagerService object.
   *
   * @param Drupal\fontawesome\FontAwesomeManagerInterface $font_awesome_manager
   *   The font awesome manager interface.
   */
  public function __construct(FontAwesomeManagerInterface $font_awesome_manager) {
    $this->fontAwesomeManager = $font_awesome_manager;
  }

  /**
   * Get icons.
   *
   * @return array
   *   List of all icons.
   */
  public function getIcons() {
    $icons = [];
    $iconData = $this->fontAwesomeManager->getIcons();
    $classes = [];

    foreach ($iconData as $icon => $data) {
      foreach ($iconData[$icon]['styles'] as $style) {
        switch ($style) {
          case 'brands':
            $iconPrefix = 'fab';
            break;

          case 'light':
            $iconPrefix = 'fal';
            break;

          case 'regular':
            $iconPrefix = 'far';
            break;

          case 'thin':
            $iconPrefix = 'fat';
            break;

          case 'duotone':
            $iconPrefix = 'fad';
            break;

          case 'kit_uploads':
            $iconPrefix = 'fak';
            break;

          default:
          case 'solid':
            $iconPrefix = 'fas';
            break;
        }
        $classes[$icon][] = $iconPrefix . ' fa-' . $icon;
      }
      $icons[] = [
        'name' => $iconData[$icon]['name'],
        'search_terms' => $iconData[$icon]['search_terms'],
        'classes' => $classes[$icon],
      ];
    }

    return $icons;
  }

  /**
   * Format icon list.
   *
   * @param array $icons
   *   A list of icons to format.
   *
   * @return array
   *   A formatted icon list.
   */
  public function formatIconList(array $icons) {
    $icon_list = [];
    foreach ($icons as $properties) {
      $icon_list[] = implode(', ', $properties['classes']);
    }
    $formatted_icon_list = explode(', ', implode(', ', $icon_list));
    return $formatted_icon_list;
  }

  /**
   * Format search terms.
   *
   * @param array $icons
   *   A list of icons to search.
   *
   * @return array
   *   A list of formatted search terms.
   */
  public function formatSearchTerms(array $icons) {
    $terms_list = [];
    foreach ($icons as $properties) {
      foreach ($properties['classes'] as $item) {
        array_push($properties['search_terms'], $properties['name']);
        $terms_list[] = $properties['search_terms'];
      }
    }
    return $terms_list;
  }

  /**
   * Get formatted icon list.
   *
   * @return array
   *   Formatted list of icons.
   */
  public function getFormattedIconList() {
    $icon_list = $this->formatIconList($this->getIcons());
    return $icon_list;
  }

  /**
   * Get formatted term list.
   *
   * @return array
   *   Formatted list of terms.
   */
  public function getFormattedTermList() {
    $terms_list = $this->formatSearchTerms($this->getIcons());
    return $terms_list;
  }

  /**
   * Get icon base name from class.
   *
   * @param string $class
   *   The class we are pulling the base name from.
   *
   * @return string
   *   The base name for the icon.
   */
  public function getIconBaseNameFromClass($class) {
    list($prefix, $base) = explode('fa-', $class);
    return $base;
  }

  /**
   * Get icon prefix from class.
   *
   * @param string $class
   *   The class we are pulling the prefix from.
   *
   * @return string
   *   The icon prefix.
   */
  public function getIconPrefixFromClass($class) {
    list($prefix, $base) = explode('fa-', $class);
    return trim($prefix);
  }

}
