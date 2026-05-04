<?php

namespace Drupal\nys_webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'nys_contact_composite' element.
 *
 * Sub-element machine names intentionally match the site's user entity fields
 * and address module sub-keys so that CRM ingestion via nys_accumulator sees
 * consistent field names across registration, contact, and other webforms.
 *
 * The corresponding render element is
 * Drupal\nys_webform\Element\NysContactComposite.
 *
 * @WebformElement(
 *   id = "nys_contact_composite",
 *   label = @Translation("NYS Contact Composite"),
 *   description = @Translation("Collects name, email, and NY address in a reusable composite element."),
 *   category = @Translation("NYS Elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class NysContactComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   *
   * Inject token defaults before prepare runs token replacement. This fires
   * even when #default_value is absent from the webform YAML (which happens
   * because the webform form builder strips values that match
   * defineDefaultProperties). Only injected when no explicit default_value
   * has been configured on the
   * element instance, so editors can override per-webform.
   *
   * administrative_area is omitted — the render element defaults to 'NY'.
   * Address token paths need environment verification; address module token
   * availability can vary.
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    if (empty($element['#default_value'])) {
      $element['#default_value'] = [
        'first_name'          => '[current-user:field_first_name]',
        'last_name'           => '[current-user:field_last_name]',
        'email'               => '[current-user:mail]',
        'address_line1'       => '[current-user:field_address:address_line1]',
        'address_line2'       => '[current-user:field_address:address_line2]',
        'locality'            => '[current-user:field_address:locality]',
        'administrative_area' => 'NY',
        'postal_code'         => '[current-user:field_address:postal_code]',
        'phone'               => '[current-user:field_user_phone_no]',
      ];
    }
    parent::prepare($element, $webform_submission);
  }

}
