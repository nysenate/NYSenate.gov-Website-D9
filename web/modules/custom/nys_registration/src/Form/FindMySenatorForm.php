<?php

namespace Drupal\nys_registration\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
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
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructor for service injection.
   */
  public function __construct(
    RegistrationHelper $helper,
    SenatorsHelper $senatorsHelper,
    MessengerInterface $messenger,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->helper = $helper;
    $this->senatorsHelper = $senatorsHelper;
    $this->messenger = $messenger;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('nys_registration.helper'),
      $container->get('nys_senators.senators_helper'),
      $container->get('messenger'),
      $container->get('tempstore.private')
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
        '#markup' => '<h2 class="nys-title">' . $title . '</h2>',
        '#weight' => 1,
      ],
      'intro' => [
        '#markup' => '<p>Please enter your street address and zip code to find out who your Senator is.</p>',
        '#weight' => 10,
      ],
      'field_address' => $this->getAddressDefinition(),
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Find My Senator',
        '#weight' => 60,
      ],
      'result' => ['#markup' => '', '#weight' => 100],
    ];
    $form['#attached']['library'][] = 'nysenate_theme/nysenate-user-profile';
    $form['#prefix'] = '<div class="c-login"><div class="c-find-my-senator">';
    $form['#suffix'] = '</div></div>';

    if ($form_state->isSubmitted()) {
      $district = $form_state->get('district');

      if ($district) {
        // Set Senator and District markup.
        $party = [];
        $address = $form_state->getValue('field_address') ?? [];
        $district_term = $form_state->get('district_term');
        $district_map = $district_term->get('field_map_url')->value;
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

        // Senator microsite link.
        $senator_link = \Drupal::service('nys_senators.microsites')
          ->getMicrosite($senator_term);

        // Create Account message markup.
        $create_message = '';
        if (!\Drupal::currentUser()->isAuthenticated()) {
          $create_message = '
            <div class="row">
              <div class="columns large-12">
                <h2 class="c-container--title">Connect</h2>
                <hr/>
                <p>
                  <a class="c-find-my-senator--senator-link" href="/user/register">
                    Create an account
                  </a>
                  on nysenate.gov so you can share your thoughts and feedback with
                  your senator.
                </p>
              </div>
          </div>';
        }

        $form['#attached']['library'][] = 'nysenate_theme/nysenate-user-profile';
        $form['result']['#markup'] = new FormattableMarkup('
          <div class="c-find-my-senator--results">
            <div class="row c-find-my-senator--row">
              <div class="columns medium-6 l-padded-column">
                <h2 class="c-container--title">Your Senator</h2>
                <hr class="c-find-my-senator--divider"/>
                <img class="c-find-my-senator--senator-img" src="' . $image . '"/>
                <div class="c-find-my-senator--district-info">
                  <p>
                    <a class="c-find-my-senator--senator-link" href="' . $senator_link . '">
                       ' . $senator_name . '
                    </a>
                  </p>
                  <p>NY Senate District ' . $district . '</p>
                </div>
                <div>
                  <p class="c-login-create">
                    <a class="c-msg-senator icon-before__contact" href="' . $senator_link . '/contact">
                      Message Senator
                    </a>
                  </p>
                </div>
              </div>
              <div class="columns medium-6 r-padded-column">
                <h2 class="c-container--title">Matched Address</h2>
                <hr class="c-find-my-senator--divider"/>
                <p class="c-find-my-senator--address-line">
                  ' . $address['address_line1'] . '
                </p>
                <p class="c-find-my-senator--address-line">
                  ' . $address['locality'] . ', ' . $address['administrative_area'] . ' ' .
                  $address['postal_code'] . '
                </p>
              </div>
            </div>
            ' . $create_message . '
            <div class="row c-find-my-senator--row">
              <div class="columns large-12">
                <h2 class="c-container--title">Senate District Map</h2>
                <hr class="c-find-my-senator--divider"/>
                <iframe class="c-find-my-senator--map-frame" src="' . $district_map . '">
                </iframe>
              </div>
            </div>
          </div>',
          []
        );
        $form['result']['#weight'] = 9;
      }
      else {
        // Set error message.
        $form['result']['#markup'] = '
          <div class="c-find-my-senator--results">
            <p>Sorry we couldn\'t find a matching senate district based on the address you provided. Please
              check the address and try again.
            </p>
          </div>';
        $form['result']['#weight'] = 9;
      }
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

    // Save input address to current session for later reuse.
    $tempstore = $this->tempStoreFactory->get('nys_registration');
    $tempstore->set('find_my_senator_address', $address);

    $district_term = $this->helper->getDistrictFromAddress($address);
    $district_num = $district_term ? $district_term->field_district_number->value : 0;
    $form_state->set('district_term', $district_term)
      ->set('district', $district_num)
      ->set('district_success', (bool) $district_num);
    $form_state->setRebuild();
  }

}
