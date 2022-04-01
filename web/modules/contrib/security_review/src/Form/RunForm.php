<?php

namespace Drupal\security_review\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\security_review\Checklist;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  /**
   * The security_review.checklist service.
   *
   * @var \Drupal\security_review\Checklist
   */
  protected $checklist;

  /**
   * Constructs a RunForm.
   *
   * @param \Drupal\security_review\Checklist $checklist
   *   The security_review.checklist service.
   */
  public function __construct(Checklist $checklist) {
    $this->checklist = $checklist;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('security_review.checklist')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'security-review-run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->currentUser()->hasPermission('run security checks')) {
      return [];
    }

    $form['run_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Run'),
      '#description' => $this->t('Click the button below to run the security checklist and review the results.') . '<br />',
      '#open' => TRUE,
    ];

    $form['run_form']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run checklist'),
    ];

    // Return the finished form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'operations' => [],
      'finished' => '_security_review_batch_run_finished',
      'title' => $this->t('Performing Security Review'),
      'init_message' => $this->t('Security Review is starting.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
    ];

    foreach ($this->checklist->getEnabledChecks() as $check) {
      $batch['operations'][] = [
        '_security_review_batch_run_op',
        [$check],
      ];
    }

    batch_set($batch);
  }

}
