<?php

namespace Drupal\security_review\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\security_review\Checklist;
use Drupal\security_review\Security;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings page for Security Review.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The security_review.checklist service.
   *
   * @var \Drupal\security_review\Checklist
   */
  protected $checklist;

  /**
   * The security_review.security service.
   *
   * @var \Drupal\security_review\Security
   */
  protected $security;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Constructs a SettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\security_review\Checklist $checklist
   *   The security_review.checklist service.
   * @param \Drupal\security_review\Security $security
   *   The security_review.security service.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date.formatter service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Checklist $checklist, Security $security, SecurityReview $security_review, DateFormatterInterface $dateFormatter) {
    parent::__construct($config_factory);
    $this->checklist = $checklist;
    $this->security = $security;
    $this->securityReview = $security_review;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('security_review.checklist'),
      $container->get('security_review.security'),
      $container->get('security_review'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'security-review-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the list of checks.
    $checks = $this->checklist->getChecks();

    // Get the user roles.
    $roles = user_roles();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    // Notify the user if anonymous users can create accounts.
    $message = '';
    if (in_array(AccountInterface::AUTHENTICATED_ROLE, $this->security->defaultUntrustedRoles())) {
      $message = $this->t('You have allowed anonymous users to create accounts without approval so the authenticated role defaults to untrusted.');
    }

    // Show the untrusted roles form element.
    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles'),
      '#description' => $this->t(
        'Define which roles are for less trusted users. The anonymous role defaults to untrusted. @message Most Security Review checks look for resources usable by untrusted roles.',
        ['@message' => $message]
      ),
      '#options' => $options,
      '#default_value' => $this->security->untrustedRoles(),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => TRUE,
    ];

    // Show the logging setting.
    $form['advanced']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log checklist results and skips'),
      '#description' => $this->t('The result of each check and skip can be logged to watchdog for tracking.'),
      '#default_value' => $this->securityReview->isLogging(),
    ];

    // Skipped checks.
    $values = [];
    $options = [];
    foreach ($checks as $check) {
      // Determine if check is being skipped.
      if ($check->isSkipped()) {
        $values[] = $check->id();
        $label = $this->t(
          '@name <em>skipped by UID @uid on @date</em>',
          [
            '@name' => $check->getTitle(),
            '@uid' => $check->skippedBy()->id(),
            '@date' => $this->dateFormatter->format($check->skippedOn()),
          ]
        );
      }
      else {
        $label = $check->getTitle();
      }
      $options[$check->id()] = $label;
    }
    $form['advanced']['skip'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Checks to skip'),
      '#description' => $this->t('Skip running certain checks. This can also be set on the <em>Run & review</em> page. It is recommended that you do not skip any checks unless you know the result is wrong or the process times out while running.'),
      '#options' => $options,
      '#default_value' => $values,
    ];

    // Iterate through checklist and get check-specific setting pages.
    foreach ($checks as $check) {
      // Get the check's setting form.
      $check_form = $check->settings()->buildForm();

      // If not empty, add it to the form.
      if (!empty($check_form)) {
        // If this is the first non-empty setting page initialize the 'details'
        if (!isset($form['advanced']['check_specific'])) {
          $form['advanced']['check_specific'] = [
            '#type' => 'details',
            '#title' => $this->t('Check-specific settings'),
            '#open' => FALSE,
            '#tree' => TRUE,
          ];
        }

        // Add the form.
        $sub_form = &$form['advanced']['check_specific'][$check->id()];

        $title = $check->getTitle();
        // If it's an external check, show its namespace.
        if ($check->getMachineNamespace() != 'security_review') {
          $title .= $this->t('%namespace', [
            '%namespace' => $check->getNamespace(),
          ]);
        }
        $sub_form = [
          '#type' => 'details',
          '#title' => $title,
          '#open' => TRUE,
          '#tree' => TRUE,
          'form' => $check_form,
        ];
      }
    }

    // Return the finished form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Run validation for check-specific settings.
    if (isset($form['advanced']['check_specific'])) {
      $check_specific_values = $form_state->getValue('check_specific');
      foreach ($this->checklist->getChecks() as $check) {
        $check_form = &$form['advanced']['check_specific'][$check->id()];
        if (isset($check_form)) {
          $check->settings()
            ->validateForm($check_form, $check_specific_values[$check->id()]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Frequently used configuration items.
    $check_settings = $this->config('security_review.checks');

    // Save that the module has been configured.
    $this->securityReview->setConfigured(TRUE);

    // Save the new untrusted roles.
    $untrusted_roles = array_keys(array_filter($form_state->getValue('untrusted_roles')));
    $this->securityReview->setUntrustedRoles($untrusted_roles);

    // Save the new logging setting.
    $logging = $form_state->getValue('logging') == 1;
    $this->securityReview->setLogging($logging);

    // Skip selected checks.
    $skipped = array_keys(array_filter($form_state->getValue('skip')));
    foreach ($this->checklist->getChecks() as $check) {
      if (in_array($check->id(), $skipped)) {
        $check->skip();
      }
      else {
        $check->enable();
      }
    }

    // Save the check-specific settings.
    if (isset($form['advanced']['check_specific'])) {
      $check_specific_values = $form_state->getValue('check_specific');
      foreach ($check_specific_values as $id => $values) {
        // Get corresponding Check.
        $check = $this->checklist->getCheckById($id);

        // Submit parameters.
        $check_form = &$form['advanced']['check_specific'][$id]['form'];
        $check_form_values = $check_specific_values[$id]['form'];

        // Submit.
        $check->settings()->submitForm($check_form, $check_form_values);
      }
    }

    // Commit the settings.
    $check_settings->save();

    // Finish submitting the form.
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['security_review.checks'];
  }

}
