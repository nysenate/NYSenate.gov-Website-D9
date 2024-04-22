<?php

namespace Drupal\nys_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to manage a user's subscribed issues, bills, or committees.
 */
class ManageDashboardForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nys_dashboard_manage_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['type_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter type'),
      '#options' => [
        'bills' => 'Bills',
        'issues' => 'Issues',
        'committees' => 'Committees',
      ],
    ];
    $form['bills'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Bills You're Following"),
      '#options' => [
        '1' => 'Bill-1',
        '2' => 'Bill-2',
        '3' => 'Bill-3',
      ],
    ];
    $form['issues'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Issues You're Following"),
      '#options' => [
        '1' => 'Issue-1',
        '2' => 'Issue-2',
        '3' => 'Issue-3',
      ],
    ];
    $form['committees'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Committees You're Following"),
      '#options' => [
        '1' => 'Committee-1',
        '2' => 'Committee-2',
        '3' => 'Committee-3',
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update my preferences'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
