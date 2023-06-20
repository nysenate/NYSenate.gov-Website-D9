<?php

namespace Drupal\nys_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\nys_registration\RegistrationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for "Find My Senator" functionality.
 */
class FindMySenatorForm extends FormBase {

  /**
   * NYS Registration Helper service.
   *
   * @var \Drupal\nys_registration\RegistrationHelper
   */
  protected RegistrationHelper $helper;

  /**
   * Constructor for service injection.
   */
  public function __construct(RegistrationHelper $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
          $container->get('nys_registration.helper')
      );
  }

  /**
   * Forces the country code form control to be hidden.
   *
   * The country code element does not exist until after the build process.  The
   * module requires it to be present and populated on submission; we want it to
   * be populated with 'US' (no user input), but not visible on the form.
   * Setting #access or #disabled will not work.  Is there a better way?
   */
  public function hideCountryField(array $element): array {
    $element['country_code']['#attributes']['style'] = 'display:none;';
    return $element;
  }

  /**
   * Defines the field array used to display an empty address field.
   *
   * @return array
   *   An array appropriate to assign to $form['field_address'].
   */
  protected function getAddressDefinition(): array {
    return [
      '#after_build' => ['::hideCountryField'],
      '#type' => 'address',
      '#default_value' => [
        'country_code' => 'US',
        'administrative_area' => 'NY',
      ],
      '#field_overrides' => [
        'addressLine1' => 'required',
        'addressLine2' => 'optional',
        'administrativeArea' => 'required',
        'locality' => 'required',
        'postalCode' => 'required',
        'familyName' => 'hidden',
        'givenName' => 'hidden',
        'organization' => 'hidden',
      ],
      '#weight' => 50,
    ];
  }

  /**
   * Primary page for "Find My Senator".
   *
   * @return array
   *   A render-able array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Initialize the form and result.
    $title = $this->getRouteMatch()->getRouteObject()->getDefault('_title');
    $form = [
      'title' => [
        '#markup' => '<h1>' . $title . '</h1>',
      ],
      'intro' => [
        '#markup' => 'Please enter your street address and zip code to find out who your Senator is.',
        '#weight' => 10,
      ],
      'field_address' => $this->getAddressDefinition(),
      'submit' => ['#type' => 'submit', '#value' => 'Submit', '#weight' => 60],
      'result' => ['#markup' => '', '#weight' => 100],
    ];

    if ($form_state->isSubmitted()) {
      $district = $form_state->get('district');
      $form['result']['#markup'] = $district
            ? '<h3>This address belongs to district ' . $district . '</h3>'
            : 'A district assignment could not be made.  Please verify the address.';
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_registration_find_my_senator';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $address = $form_state->getValue('field_address') ?? [];
    $district_term = $this->helper->getDistrictFromAddress($address);
    $district_num = $district_term ? $district_term->field_district_number->value : 0;
    $form_state->set('district_term', $district_term)
      ->set('district', $district_num)
      ->set('district_success', (bool) $district_num);
    $form_state->setRebuild();
  }

}
