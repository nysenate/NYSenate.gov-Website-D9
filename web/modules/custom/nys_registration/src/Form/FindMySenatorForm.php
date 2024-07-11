<?php

namespace Drupal\nys_registration\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\nys_registration\RegistrationHelper;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\taxonomy\Entity\Term;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for "Find My Senator" functionality.
 */
class FindMySenatorForm extends FormBase {

  /**
   * List of address fields to include in the variable going to Twig.
   *
   * @var array
   */
  private array $twigFields = [
    'address_line1',
    'address_line2',
    'locality',
    'administrative_area',
    'postal_code',
  ];

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
   * Default object for messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Log facility for nys_registration.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Drupal's Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor for service injection.
   */
  public function __construct(
    RegistrationHelper $helper,
    SenatorsHelper $senatorsHelper,
    MessengerInterface $messenger,
    PrivateTempStoreFactory $tempStoreFactory,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->helper = $helper;
    $this->senatorsHelper = $senatorsHelper;
    $this->messenger = $messenger;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $this->getLogger('nys_find_my_senator');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('nys_registration.helper'),
      $container->get('nys_senators.senators_helper'),
      $container->get('messenger'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
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
        'addressLine3' => 'hidden',
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
    // @todo The explicit title is required because of custom code.
    // Something in our custom theme or modules is disabling the internal title
    // block normally created by Drupal.  Once that is found and killed, the
    // title field found here can be removed.
    $title = $this->getRouteMatch()?->getRouteObject()?->getDefault('_title')
      ?? "Find My Senator";
    $form = [
      '#prefix' => '<div class="c-find-my-senator">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => ['nys_registration/find_my_senator'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $title,
        '#attributes' => ['class' => 'nys-title'],
        '#weight' => 1,
      ],
      'intro' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Please enter your street address and zip code to find out who your Senator is.',
        '#weight' => 50,
      ],
      'field_address' => $this->getAddressDefinition(),
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Find My Senator',
        '#weight' => 60,
      ],
    ];

    if ($form_state->isSubmitted()) {
      // Normalize the address fields.
      $submitted = $form_state->getValue('field_address') ?? [];
      $address = [];
      foreach ($this->twigFields as $field) {
        $address[$field] = $submitted[$field] ?? '';
      }

      // Set up the senator's card.
      $district_term = $form_state->get('district_term');
      $senator_term = $district_term?->field_senator?->entity ?? NULL;
      $senator = ($senator_term instanceof Term)
        ? $this->entityTypeManager->getViewBuilder('taxonomy_term')
          ->view($senator_term, 'senator_search_list')
        : NULL;
      $district = $district_term instanceof Term
        ? $this->entityTypeManager->getViewBuilder('taxonomy_term')
          ->view($district_term, 'matched_district')
        : NULL;
      $form['result'] = [
        '#theme' => 'nys_find_my_senator',
        '#address' => $address,
        '#district' => $district,
        '#district_term' => $district_term,
        '#map_url' => $district_term->field_map_url->value ?? '',
        '#senator' => $senator,
        '#is_anonymous' => $this->currentUser()->isAnonymous(),
        '#weight' => 30,
      ];
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $address = $form_state->getValue('field_address') ?? [];

    // Save input address to current session for later reuse.
    try {
      $this->tempStoreFactory->get('nys_registration')
        ->set('find_my_senator_address', $address);
    }
    catch (\Throwable $e) {
      // Do nothing.  The only side effect is that the form will not be auto-
      // populated with the previously submitted address.  Oh well.
    }

    $form_state->set('district_term', $this->helper->getDistrictFromAddress($address))
      ->setRebuild();
  }

}
