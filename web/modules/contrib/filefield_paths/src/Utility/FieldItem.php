<?php

namespace Drupal\filefield_paths\Utility;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;

/**
 * Field Item Utility.
 */
final class FieldItem {

  /**
   * Get filefield_paths field settings.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $fieldItemList
   *   A field item to check for settings.
   *
   * @return array
   *   The filefield_paths settings for the field if set, else empty.
   */
  public static function getConfiguration(FieldItemListInterface $fieldItemList): array {
    $definition = $fieldItemList->getFieldDefinition();
    if ($definition instanceof ThirdPartySettingsInterface) {
      return $definition->getThirdPartySettings('filefield_paths');
    }
    return [];
  }

  /**
   * Check if filefield_paths is enabled for a field item.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface|mixed $field
   *   A field to check.
   *
   * @return bool
   *   State of filefield_path functionality for a given file field.
   */
  public static function hasConfigurationEnabled($field): bool {
    return $field instanceof FileFieldItemList &&
      (self::getConfiguration($field)['enabled'] ?? FALSE);
  }

  /**
   * Field widget helper.
   *
   * @param $element
   *   Widget element.
   * @param $context
   *   Widget context.
   *
   * @return \Drupal\file\Plugin\Field\FieldType\FileFieldItemList|null
   *   Returns Field Item List instance. Null if widget type is not supported.
   */
  public static function getFromSupportedWidget(array $element, array $context): ?FileFieldItemList {
    if(isset($element['#type']) && $element['#type'] === 'managed_file') {
      return $context['items'] ?? NULL;
    }
    return NULL;
  }

}
