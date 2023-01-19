<?php

namespace Drupal\nys_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\nys_registration\RegistrationHelper;
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
   * {@inheritDoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, RegistrationHelper $helper, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $language_manager, $entity_type_bundle_info, $time);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('nys_registration.helper'),
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

    return $form;
  }

  /**
   * Step 1 validation handler.
   *
   * This should satisfy the "entity must be validated" requirement.
   */
  public function formValidateStep1(array &$form, FormStateInterface $form_state) {
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

    // Record the district info in form state, and move to the next step.
    $form_state->setValue('field_district', [$district_id])
      ->set('senate_district', $district_num)
      ->set('district', $district_term)
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
    $blurb = $this->t('If your address is incorrect, please click "BACK" to edit it.  Otherwise, select "FINISH" to create your account.');
    $assignment = $district
      ? "This address is in Senate District $district."
      : "We were unable to place your address in a Senate district.";
    $form['registration_wizard'] = [
      '#type' => 'container',
      'district_assignment' => [
        '#markup' => '<h1>' . $assignment . '</h1>',
      ],
      'instruction' => ['#markup' => '<p>' . $blurb . '</p>'],
    ];
    $form['actions'] = [
      'next' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Next'),
        '#submit' => ['::formSubmitStep2'],
      ],
      'back' => [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::formSubmitBack'],
        '#limit_validation_errors' => [],
      ],
    ];
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
    $form['registration_wizard'] = [
      '#markup' => '<h1>Almost there!</h1><p>Please find the email that was just sent to you. Click on the login URL in the email (or paste it into your browser) to validate your address and set up a password. New users must login with 7 days to retain an active account. Once that\'s done, you\'ll be ready to participate in the legislative process.</p>',
    ];
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
