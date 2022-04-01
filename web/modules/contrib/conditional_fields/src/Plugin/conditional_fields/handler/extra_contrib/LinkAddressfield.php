<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler\extra_contrib;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for links provided by the Addressfield module.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_address_default",
 * )
 */
class LinkAddressfield extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    if ($options['values_set'] != ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
      return $state;
    }

    $regex = $options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX;
    $keys = [];

    if ($field['#handlers']['address']) {
      $keys += [
        'country',
        'thoroughfare',
        'premise',
        'postal_code',
        'locality',
        'administrative_area',
      ];
    }

    if ($field['#handlers']['organisation']) {
      $keys += ['organisation_name'];
    }

    if ($field['#handlers']['name-oneline']) {
      $keys += ['name_line'];
    }
    elseif ($field['#handlers']['name-full']) {
      $keys += ['first_name', 'last_name'];
    }

    $addressfield_selectors = [];

    foreach ($keys as $key) {
      $addressfield_selectors[str_replace('%key', $key, $options['selector'])] = ['value' => $regex ? $options['value'] : $options['value'][0][$key]];
    }

    $state = [$options['state'] => $addressfield_selectors];

    return $state;
  }

}
