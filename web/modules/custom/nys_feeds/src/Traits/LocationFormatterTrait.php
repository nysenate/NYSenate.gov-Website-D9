<?php

namespace Drupal\nys_feeds\Traits;

use Drupal\address\Plugin\Field\FieldType\AddressFieldItemList;

/**
 * Export a location field to an array.
 */
trait LocationFormatterTrait {

  /**
   * Forms a reasonable array based on a location field.
   *
   * If anything can't be resolved, an empty array is returned.
   */
  protected function getLocation(AddressFieldItemList $location_field): array {

    // Collect the address fields.
    try {
      /** @var \Drupal\address\AddressInterface $item */
      $item = $location_field->first();
      $item_array = $item->toArray() ?? [];
      $location = array_map(
        fn($val) => $val ?? '',
        $item_array
      );
    }
    catch (\Exception) {
      $location = [];
    }
    return $location;
  }

}
