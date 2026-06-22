<?php

namespace Drupal\nys_webform\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a render element for the NYS Contact Composite webform element.
 *
 * Sub-element machine names match the site's user entity fields and address
 * module sub-keys for CRM compatibility via nys_accumulator.
 *
 * @FormElement("nys_contact_composite")
 */
class NysContactComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme' => 'webform_composite_nys_contact_composite'] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];

    // --- Name ---
    $elements['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
    ];
    $elements['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
    ];

    // --- Contact ---
    $elements['email'] = [
      '#type' => 'email',
      '#title' => t('Email address'),
    ];
    $elements['phone'] = [
      '#type' => 'tel',
      '#title' => t('Phone number'),
    ];

    // --- Address ---
    // Machine names match address module sub-keys used in RegisterForm,
    // FindMySenatorForm, and expected by nys_accumulator CRM ingestion.
    $elements['address_line1'] = [
      '#type' => 'textfield',
      '#title' => t('Street address'),
    ];
    $elements['address_line2'] = [
      '#type' => 'textfield',
      '#title' => t('Address 2'),
    ];
    $elements['locality'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
    ];
    $elements['administrative_area'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => 'state_codes',
      '#default_value' => 'NY',
    ];
    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Zip code'),
      '#size' => 10,
      '#maxlength' => 10,
    ];

    return $elements;
  }

}
