<?php

namespace Drupal\nys_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\nys_registration\RegistrationHelper;
use Drupal\nys_senators\SenatorsHelper;
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
   * NYS Senators Helper service.
   *
   * @var \Drupal\nys_senators\SenatorsHelper
   */
  protected SenatorsHelper $senatorsHelper;

  /**
   * Default object for messenger serivce.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor for service injection.
   */
  public function __construct(RegistrationHelper $helper, SenatorsHelper $senatorsHelper, MessengerInterface $messenger) {
    $this->helper = $helper;
    $this->senatorsHelper = $senatorsHelper;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('nys_registration.helper'),
      $container->get('nys_senators.senators_helper'),
      $container->get('messenger'),
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

      if ($district) {
        // Set Senator and District markup.
        $party = [];
        $district_term = $form_state->get('district_term');
        $senator_term = $district_term ? $district_term->get('field_senator')->entity : 0;
        $senator_name = $senator_term->get('field_senator_name')->getValue();
        $senator_name = $senator_name[0]['given'] . ' ' . $senator_name[0]['family'];
        $mid = $senator_term->get('field_member_headshot')->target_id;
        $media = Media::load($mid);
        $fid = $media->get('field_image')->target_id;
        $file = File::load($fid);
        $image = empty($file) ?
          '/themes/custom/nysenate_theme/src/assets/default-avatar.png' :
          \Drupal::service('file_url_generator')
            ->generateAbsoluteString($file->getFileUri());

        $parties = $this->senatorsHelper->getPartyNames($senator_term);
        foreach ($parties as $value) {
          $party[] = $value;
        }

        $location = $this->helper->getMicrositeDistrictAlias($senator_term);

        // Senator microsite link.
        $senator_link = \Drupal::service('nys_senators.microsites')
          ->getMicrosite($senator_term);

        $form['#attached']['library'][] = 'nysenate_theme/nysenate-user-profile';
        $form['result']['#markup'] = '
          <div class="c-login">
            <div class="c-login-left">
              <div class="nys-senator--thumb">
                <img src="'. $image .'" alt="Senator ' . $senator_name . ' avatar"/>
              </div>
              <ul class="c-senator--info">
                <li>Your Senator</li>
                <li>' . $senator_name . '</li>
                <li>' . $party[0] . '</li>
                <li>
                  NY Senate District ' . $district . ' ' .
                  ($location ? '(<a href="' . $location . '">Map</a>)' : '') . '
                </li>
              </ul>
            </div>
            <a class="c-msg-senator icon-before__contact" href="'. $senator_link .'/contact">Message Senator</a>
          </div>';
          // dump($form['result']['#markup']);die;
      }
      else {
        // Set error message.
        // dump($this);
        $this->messenger->addError('A district assignment could not be made.  Please verify the address.');
      }
      // $form['result']['#markup'] = $district
      //       ? '<h3>This address belongs to district ' . $district . '</h3>'
      //       : 'A district assignment could not be made.  Please verify the address.';
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
