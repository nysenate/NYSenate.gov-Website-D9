<?php

namespace Drupal\address_autocomplete\Element;

use Drupal\address\Element\Address;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an address_autocomplete form element.
 *
 * Usage example:
 *
 * @code
 * $form['address_autocomplete'] = [
 *   '#type' => 'address_autocomplete',
 * ];
 * @endcode
 *
 * @FormElement("address_autocomplete")
 */
class AddressAutocomplete extends Address {

  /**
   * @inheritDoc
   */
  public function getInfo() {
    $info = parent::getInfo();

    $info['#process'][] = [
      get_class($this),
      'processAutocomplete',
    ];

    return $info;
  }

  /**
   * @inheritDoc
   */
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $element["#attached"]["library"][] = 'address_autocomplete/address_autocomplete';
    $element["address_line1"]['#autocomplete_route_name'] = 'address_autocomplete.addresses';
    $element["address_line1"]["#attributes"]['placeholder'] = t('Please start typing your address...');
    return $element;
  }

}
