<?php

namespace Drupal\nys_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\nys_registration\RegistrationHelper;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\user\RegisterForm as UserRegisterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

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
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, RegistrationHelper $helper, SenatorsHelper $senatorsHelper, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->helper = $helper;
    $this->senatorsHelper = $senatorsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('nys_registration.helper'),
      $container->get('nys_senators.senators_helper'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
    // Initialize the form's entity.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    // Ensure incoming values are saved in state.
    $this->compileValues($form_state);

    // Retrieve the appropriate form for this step.
    return $this->buildFormStep($form, $form_state);
  }

  /**
   * Ensures submitted values from all steps are saved in form state.
   */
  protected function compileValues(FormStateInterface $form_state) {
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
        '#validate' => ['::formValidateStep1'],
      ],
    ];

    // Check if redirected from Senator's Page.
    if (\Drupal::request()->headers->get('referer')) {
      $referrer = explode('/', \Drupal::request()->headers->get('referer'));

      if ($referrer[3] == 'senators') {
        $form['#theme'] = 'register_form_step1_message_senator';
        $title_text = 'Message a Senator';
        $help_text = 'To send a message to any NY State Senator, please creating a profile or <a href="/user/login">login</a>.';

        $query_parameters = \Drupal::request()->query->all();

        if (isset($query_parameters['senator'])) {
          $senator = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->load($query_parameters['senator']);

          if ($senator->field_senator_name) {
            $given = $senator->field_senator_name->given ?? '';
            $family = $senator->field_senator_name->family ?? '';
            $senator_name = trim($given . ' ' . $family);

            $title_text = 'Message Sen. ' . $senator_name;
            $help_text = t('To send a message to NY State Sen.') .
              ' ' . trim($family) . ', ' .
              t('please create a profile using the form below or <a href="/user/login">login</a>.');
          }

          $form['registration_teaser'] = [
            '#markup' => '<div class="c-login">
                            <h2 class="nys-title">' . $title_text . '</h2>
                            <p class="body">' . $help_text . '</p>',
            '#weight' => -100,
          ];
          $form['actions']['#suffix'] = '</div>';

          $form['#attached']['library'][] = 'nysenate_theme/nysenate-user-profile';
        }

      }
    }

    return $form;
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
  public function formValidateStep1(array &$form, FormStateInterface $form_state) {
    $this->compileValues($form_state);

    // Prep some values.
    $mail = $form_state->getValue('mail', '');
    $first_name = $form_state->getValue(['field_first_name', '0', 'value'], '');
    $last_name = $form_state->getValue(['field_last_name', '0', 'value'], '');
    $full_check = $mail . $first_name . $last_name;

    // Nuclear option for russian accounts: disallow all non-Latin characters.
    // $has_non_latin = preg_match('/[^\\p{Common}\\p{Latin}]/u', $full_check)
    $is_ru = (str_ends_with($mail, '.ru'));
    // Check if valid email and only contains latin characters.
    $valid_email = (boolean) preg_match('/^[\w\-\.]+@([A-Za-z0-9]+\.)+[A-Za-z]{2,}$/u', $full_check);

    // Enforce ban on .ru email addresses and cyrillic characters.
    if ($is_ru || !$valid_email) {
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
  public function formSubmitStep1(array &$form, FormStateInterface $form_state) {
    // Process the district.
    $address = $form_state->getValue(['field_address', '0', 'address']) ?? [];
    $district_term = $this->helper->getDistrictFromAddress($address);
    $district_id = $district_term?->id();
    $district_num = $district_term ? $district_term->field_district_number->value : 0;
    $senator = $district_term ? $district_term->get('field_senator')->entity : 0;

    // Record the district info in form state.
    $form_state->setValue('field_district', [$district_id])
      ->set('senate_district', $district_num)
      ->set('district', $district_term);
    // Record the senator info in form state, and move to the next step.
    $form_state->setValue('field_senator', [$senator])
      ->set('senator', $senator)
      ->set('step', 2)
      ->setRebuild();

    // Update the entity with all the form values (incl. district)
    $this->submitForm($form, $form_state);
  }

  /**
   * Step 2 displays district info, and asks for confirmation.
   */
  public function formBuildStep2(array &$form, FormStateInterface $form_state): array {
    $district = $form_state->get('senate_district') ?? 0;
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
        \Drupal::service('file_url_generator')
          ->generateAbsoluteString($file->getFileUri());
      $senator['party'] = $this->senatorsHelper->getPartyNames($senator_term);
      $senator['location'] = $this->helper->getMicrositeDistrictAlias($senator_term);
      $senator['name'] = $senator_name[0]['given'] . ' ' . $senator_name[0]['family'];
      $first_name = $form_state->getValue('field_first_name');
      $last_name = $form_state->getValue('field_last_name');
      $address = $form_state->getValue(['field_address', '0', 'address']);
      $user['name'] = $first_name[0]['value'] . ' ' . $last_name[0]['value'];
      $user['address_1'] = $address['address_line1'];
      $user['address_2'] = $address['address_line2'];
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
    if (!$senator_term) {
      $form['#theme'] = 'register_form_step2_not_found';
    }
    else {
      $form['#theme'] = 'register_form_step2';
      $form['#attributes']['variables'] = [
        'district_number' => $district,
        'senator' => json_encode($senator),
        'user' => json_encode($user),
      ];
    }
    return $form;
  }

  /**
   * Step 2 submit handler.
   *
   * Make sure confirmations are checked, save the entity, and send email.
   */
  public function formSubmitStep2(array $form, FormStateInterface $form_state) {
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
  public function formSubmitBack(array &$form, FormStateInterface $form_state) {
    // Reset the district information in form state.
    $form_state->setValue('field_district', NULL)
      ->set('senate_district', 0)
      ->set('step', 1)
      ->setRebuild();

    // Make sure values is set properly.
    $this->compileValues($form_state);
  }

}
