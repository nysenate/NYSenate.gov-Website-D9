<?php

namespace Drupal\nys_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\nys_registration\RegistrationHelper;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\user\RegisterForm as UserRegisterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom multi-step registration form.
 */
class RegisterForm extends UserRegisterForm {

  /**
   * Registration Helper service.
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
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Drupal's File URL Generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    RegistrationHelper $helper,
    SenatorsHelper $senatorsHelper,
    PrivateTempStoreFactory $tempStoreFactory,
    EntityRepositoryInterface $entity_repository,
    LanguageManagerInterface $language_manager,
    FileUrlGeneratorInterface $fileUrlGenerator,
    ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    ?TimeInterface $time = NULL,
  ) {
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->helper = $helper;
    $this->senatorsHelper = $senatorsHelper;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('nys_registration.helper'),
      $container->get('nys_senators.senators_helper'),
      $container->get('tempstore.private'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('file_url_generator'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the form state values for the next step.
    $this->compileValues($form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ContentEntityInterface {
    $this->compileValues($form_state);

    return parent::validateForm($form, $form_state);
  }

  /**
   * Validation algorithm for zip code.
   */
  protected function validateZipCode(string $zip): bool {
    return preg_match('/^[0-9]{5}([- ]?[0-9]{4})?$/', $zip);
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'nys_registration_register_form';
  }

  /**
   * {@inheritdoc}
   *
   * This form will only be used for new users, so cache dependencies are not
   * a consideration.  Also, ensure the form state is populated with all values
   * collected from all steps, and use a director to decide which step to build.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $route_match = \Drupal::routeMatch();
    $current_route_name = $route_match->getRouteName();
    if ($current_route_name === 'user.admin_create') {
      // Return the standard registration form for the user.admin_create route.
      return parent::buildForm($form, $form_state);
    }
    else {
      // Initialize the form's entity.
      if (!$form_state->has('entity_form_initialized')) {
        $this->init($form_state);
      }
      // Ensure incoming values are saved in state.
      $this->compileValues($form_state);
      // Retrieve the appropriate form for this step.
      return $this->buildFormStep($form, $form_state);
    }
  }

  /**
   * Ensures submitted values from all steps are saved in form state.
   */
  protected function compileValues(FormStateInterface $form_state): void {
    if ($values = $form_state->get('cached_steps')) {
      $form_state->setValues($form_state->getValues() + $values);
    }
    $form_state->set('cached_steps', $form_state->getValues());
  }

  /**
   * Retrieves the appropriate form, based on the step.
   *
   * Given a numeric step in $form_state (defaults to 1), try to call a method
   * named for that step.  If it does not exist, call the "invalid step" form.
   *
   * NOTE: Custom validators must be used to avoid validating the entity on
   * each step (default behavior calls validateForm() with each submission).
   * Over the course of the form, it is necessary to validate the entity at
   * least once.  Likewise, the parent's submitForm() and save() must be called
   * to trigger updating the cached entity and saving the entity, respectively.
   *
   * @see \Drupal\Core\Entity\ContentEntityForm::validateForm()
   */
  public function buildFormStep(array &$form, FormStateInterface $form_state): array {
    // If a builder method exists, use it, otherwise "invalid step".
    $step = $form_state->get('step') ?: 1;
    $func = 'formBuildStep' . $step;
    if (method_exists($this, $func)) {
      $form = $this->$func($form, $form_state);
    }
    else {
      $form = $this->formBuildInvalidStep($form, $form_state);
      $form_state->set('step', 0);
    }

    // The default 'actions' block in the entity form appears up near the top.
    // Force it to the bottom.
    if (array_key_exists('actions', $form)) {
      $form['actions']['#weight'] = 1000;
    }
    return $form;
  }

  /**
   * Step 1 collects all the base user information (name, address, etc.)
   */
  public function formBuildStep1(array &$form, FormStateInterface $form_state): array {
    // Get the standard user registration form.
    $form = $this->form($form, $form_state);

    // The username will be auto-populated during creation.
    $form['account']['name']['#access'] = FALSE;

    // These fields are not collected during registration.
    $disable = [
      'field_dateofbirth',
      'field_gender_user',
      'field_top_issue',
      'field_user_phone_no',
      'field_user_receive_emails',
      'field_voting_auto_subscribe',
      'field_profile_picture',
    ];
    foreach ($disable as $name) {
      $form[$name]['#access'] = FALSE;
    }

    // Add the custom validation and submission handlers.
    $form['actions'] = [
      'next' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Next'),
        '#submit' => ['::formSubmitStep1'],
        '#validate' => ['::formPreValidateStep1', '::formValidateStep1'],
      ],
    ];

    // Prefill address if value found from find my senator form.
    $tempstore = $this->tempStoreFactory->get('nys_registration');
    $find_my_senator_address = $tempstore->get('find_my_senator_address');
    if (!empty($find_my_senator_address)) {
      $form['field_address']['widget'][0]['address']['#default_value'] = $find_my_senator_address;
      $this->messenger()
        ->addStatus($this->t('We reused the address you provided in "Find My Senator".  Please check that it is correct before proceeding.'));
    }

    return $form;
  }

  /**
   * Step 1 pre-validation handler.
   *
   * Cleans/sanitizes data prior to validation.
   */
  public function formPreValidateStep1(array &$form, FormStateInterface $form_state): void {
    // Trim whitespace from Zipcode input.
    $zip_raw = $form_state
      ->getValue(['field_address', '0', 'address', 'postal_code']);
    $zip_trimmed = trim($zip_raw);
    if ($zip_raw && $zip_raw !== $zip_trimmed) {
      $form_state
        ->setValue([
          'field_address',
          '0',
          'address',
          'postal_code',
        ], $zip_trimmed);
    }

    // Auto-generate a normalized username; avoid validation errors.
    $form_state->setValue('name', $this->helper->generateUserName($form_state));
  }

  /**
   * Step 1 validation handler.
   *
   * This should satisfy the "entity must be validated" requirement.
   *
   * Due to multiple attacks which generated spam user accounts, the below
   * conditions result in rejection of the account request:
   *   - if email, first name, or last name contains cyrillic characters, or
   *   - the email address specifies the .ru TLD.
   */
  public function formValidateStep1(array &$form, FormStateInterface $form_state): void {
    $this->compileValues($form_state);

    // Prep some values.
    $mail = $form_state->getValue('mail', '');
    $first_name = $form_state->getValue(['field_first_name', '0', 'value'], '');
    $last_name = $form_state->getValue(['field_last_name', '0', 'value'], '');
    $full_check = $mail . $first_name . $last_name;

    // Nuclear option for russian accounts: disallow all non-Latin characters.
    // $has_non_latin = preg_match('/[^\\p{Common}\\p{Latin}]/u', $full_check)
    $is_ru = (str_ends_with($mail, '.ru'));
    // Check if valid email and does not contain any Cyrillic characters.
    $has_cyrillic = (boolean) preg_match('/\p{Cyrillic}/u', $full_check);

    // Enforce ban on .ru email addresses and cyrillic characters.
    if ($is_ru || $has_cyrillic) {
      $form_state->setError($form['account']['mail'], $this->t('Please enter a valid email address.'));
    }

    // Ensure the zip code matches US standards.
    $field = &$form['field_address']['widget']['0']['address']['postal_code'];
    $value = $form_state
      ->getValue(['field_address', '0', 'address', 'postal_code'], '');
    if (!$this->validateZipCode($value)) {
      $form_state->setError($field, $this->t('Please enter a valid zip code.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Step 1 submit handler.
   *
   * Process the district and ensure the entity gets updated.
   */
  public function formSubmitStep1(array &$form, FormStateInterface $form_state): void {
    // Ensure previously submitted assignments are cleared.
    $form_state->set('district', NULL)
      ->set('senator', NULL)
      ->setValue('field_district', [])
      ->setValue('field_senator', []);

    // Process the district.
    $address = $form_state->getValue(['field_address', '0', 'address']) ?? [];

    // Look up district assignment only if address is in New York.
    if (($address['administrative_area'] ?? '') == 'NY') {
      $form_state->set('is_out_of_state', FALSE);

      $district_term = $this->helper->getDistrictFromAddress($address);
      $district_id = $district_term?->id();
      $senator = $district_term ? $district_term->get('field_senator')->entity : 0;

      // Record the district info in form state.
      $form_state->setValue('field_district', [$district_id])
        ->set('district', $district_term);
      // Record the senator info in form state, and move to the next step.
      $form_state->setValue('field_senator', [$senator])
        ->set('senator', $senator);
    }
    else {
      $form_state->set('is_out_of_state', TRUE);
    }
    $form_state->set('step', 2)->setRebuild();

    // Update the entity with all the form values (incl. district)
    $this->submitForm($form, $form_state);
  }

  /**
   * Step 2 displays district info, and asks for confirmation.
   */
  public function formBuildStep2(array &$form, FormStateInterface $form_state): array {
    $district = [];
    $district_term = $form_state->get('district');
    if ($district_term) {
      $district['name'] = $district_term->name->value ?? '';
      $district['field_map_url'] = $district_term->field_map_url->value ?? '';
      $district['field_subheading'] = $district_term->field_subheading->value ?? '';
      $district['field_district_number'] = $district_term->field_district_number->value ?? '';
    }

    $senator = [];
    $senator_term = $form_state->get('senator');
    if ($senator_term) {
      $senator_name = $senator_term->get('field_senator_name')->getValue();
      $mid = $senator_term->get('field_member_headshot')->target_id;
      $media = Media::load($mid);
      $fid = $media->get('field_image')->target_id;
      $file = File::load($fid);
      $senator['image'] = empty($file) ?
        '/themes/custom/nysenate_theme/src/assets/default-avatar.png' :
        $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      $senator['party'] = $this->senatorsHelper->getPartyNames($senator_term);
      $senator['location'] = $this->helper->getMicrositeDistrictAlias($senator_term);
      $senator['name'] = $senator_name[0]['given'] . ' ' . $senator_name[0]['family'];
      $first_name = $form_state->getValue('field_first_name');
      $last_name = $form_state->getValue('field_last_name');
      $address = $form_state->getValue(['field_address', '0', 'address']);
      $user['name'] = $first_name[0]['value'] . ' ' . $last_name[0]['value'];
      $user['address_1'] = $address['address_line1'];
      $user['address_2'] = $address['address_line2'];
      $user['locality'] = $address['locality'];
      $user['administrative_area'] = $address['administrative_area'];
      $user['postal_code'] = $address['postal_code'];
      $user['mail'] = $form_state->getValue('mail');
    }
    $form['actions'] = [
      'back' => [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::formSubmitBack'],
        '#limit_validation_errors' => [],
      ],
      'next' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Finish'),
        '#submit' => ['::formSubmitStep2'],
      ],
    ];
    if (!$senator_term || !$district_term) {
      $form['#theme'] = 'register_form_step2_not_found';
    }
    else {
      $form['#theme'] = 'register_form_step2';
      $form['#attributes']['variables'] = [
        'district' => json_encode($district),
        'senator' => json_encode($senator),
        'user' => json_encode($user),
      ];
    }
    $form['#attributes']['variables']['out_of_state'] = $form_state->get('is_out_of_state') ?? FALSE;
    return $form;
  }

  /**
   * Step 2 submit handler.
   *
   * Make sure confirmations are checked, save the entity, and send email.
   */
  public function formSubmitStep2(array $form, FormStateInterface $form_state): void {
    // Save incoming values to state.
    $this->compileValues($form_state);

    // Allow the form to completely process the new user.
    parent::save($form, $form_state);

    // Go to final step, and disable form redirection.
    $form_state->set('step', 3)->setRebuild()->disableRedirect();
  }

  /**
   * Step 3 is confirmation page, with directions to check for an email.
   */
  public function formBuildStep3(array &$form, FormStateInterface $form_state): array {
    $form['#theme'] = 'register_form_step3';
    return $form;
  }

  /**
   * Error page if the step could not be found.
   */
  public function formBuildInvalidStep(array &$form, FormStateInterface $form_state): array {
    $form['registration_wizard'] = [
      '#markup' => '<h1>INVALID</h1>',
    ];
    return $form;
  }

  /**
   * Handles going back from step 2 to step 1.
   */
  public function formSubmitBack(array &$form, FormStateInterface $form_state): void {
    // Reset the district information in form state.
    $form_state->set('step', 1)
      ->setRebuild();

    // Make sure values is set properly.
    $this->compileValues($form_state);
  }

}
