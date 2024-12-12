<?php

namespace Drupal\nys_school_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to establish open submission periods for school forms.
 */
class SubmissionPeriodForm extends ConfigFormBase {

  /**
   * The system's school form types.
   */
  const SCHOOL_FORM_TYPES = [
    'thanksgiving' => 'Thanksgiving',
    'earth_day' => 'Earth Day',
  ];

  /**
   * Drupal's state interface for non-exportable config.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateInterface;

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\State\StateInterface $stateInterface
   *   The state service.
   */
  public function __construct($stateInterface) {
    $this->stateInterface = $stateInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_school_forms_submission_period_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // No config schema needed when using stateInterface.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submission_periods'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Open submission periods'),
      '#description' => $this->t('Sets the open submission periods for the Thanksgiving and Earth Day school forms.'),
      '#description_display' => 'before',
      '#tree' => TRUE,
    ];
    foreach ($this::SCHOOL_FORM_TYPES as $type => $label) {
      $submission_period_field = [
        '#type' => 'fieldset',
        '#title' => "$label submission period",
      ];
      $submission_period_field['begin'] = [
        '#type' => 'date',
        '#title' => 'Start',
        '#date_date_format' => 'Y-m-d',
        '#default_value' => $this->stateInterface->get('nys_school_forms.submission_periods')[$type]['begin'],
      ];
      $submission_period_field['end'] = [
        '#type' => 'date',
        '#title' => 'End',
        '#date_date_format' => 'Y-m-d',
        '#default_value' => $this->stateInterface->get('nys_school_forms.submission_periods')[$type]['end'],
      ];
      $form['submission_periods'][$type] = $submission_period_field;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->stateInterface->set('nys_school_forms.submission_periods', $form_state->getValue('submission_periods'));
    parent::submitForm($form, $form_state);
  }

}
