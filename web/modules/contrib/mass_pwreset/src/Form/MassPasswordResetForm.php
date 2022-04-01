<?php

namespace Drupal\mass_pwreset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Mass Password Reset Form.
 */
class MassPasswordResetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_pwreset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Role Options'),
      '#description' => $this->t('Select all users or specific roles below.'),
      '#open' => TRUE,
    ];
    $form['options']['authenticated'] = [
      '#type' => 'details',
      '#title' => $this->t('Authenticated Role'),
      '#description' => $this->t('Selecting Authenticated will reset all users.'),
      '#open' => TRUE,
    ];
    $form['options']['authenticated']['authenticated_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all users'),
      '#required' => FALSE,
    ];
    $form['options']['custom_roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#open' => TRUE,
    ];
    $form['options']['custom_roles']['selected_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Roles to Reset'),
      '#options' => mass_pwreset_get_custom_roles(),
      '#required' => FALSE,
      '#states' => [
        'disabled' => [
          ':input[name="authenticated_role"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['notify'] = [
      '#type' => 'details',
      '#title' => $this->t('Notify Users'),
      '#open' => TRUE,
    ];
    $form['notify']['notify_active_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify active users of password reset via email'),
      '#default_value' => 0,
    ];
    $form['notify']['notify_blocked_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify blocked users of password reset via email'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="notify_active_users"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Administrator Reset'),
      '#description' => $this->t('Include the administrative superuser id 1 account in the list of passwords being reset.'),
      '#open' => FALSE,
    ];
    $form['admin']['include_admin_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include admin user (uid1)'),
      '#default_value' => 0,
    ];
    // Resetting your own password causes an error at the end of the batch.
    $form['current_user_note'] = [
      '#type' => 'item',
      '#markup' => $this->t('The user submitting this form will not be included in the password reset batch.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['reset_passwords'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Passwords'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the selected roles from the form.
    $selected_roles = array_filter($form_state->getValue('selected_roles'));

    // User must select roles for mass password reset.
    if ($form_state->getValue('authenticated_role') == 0 && empty($selected_roles)) {
      $form_state->setErrorByName('authenticated_role', $this->t('Please select all users or select specific roles.'));
      return;
    }

    // Holds all the user ids to have the passwords reset.
    $uids = [];
    // If reset for all users is checked, get all the uids - excluding the
    // current user id and user 1.
    if ($form_state->getValue('authenticated_role') == 1) {
      $uids = mass_pwreset_get_uids();
      $roles = ['authenticated role'];
    }
    // Otherwise, get the ids for the selected roles - excuding the current uid.
    else {
      $roles = $selected_roles;
      $uids = mass_pwreset_get_uids_by_selected_roles($roles);
    }

    // If there are no users returned and there are roles selected, set error.
    if (empty($uids) && $selected_roles) {
      $form_state->setErrorByName('selected_roles', $this->t('There are no users with the selected role.'));
    }

    // Set the 'uids' and 'roles' values for use in submitForm.
    $form_state->set('uids', $uids);
    $form_state->set('roles', $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get 'uids' and 'roles' values set in form validation.
    $uids = $form_state->get('uids');
    $roles = $form_state->get('roles');

    // If the admin user is to be included in the reset and the current user is
    // NOT the super admin user, add the uid of 1 to the $uids array.
    // A check to see if the current user is 1 is required because resetting
    // your own password will make the batch fail.
    if ($form_state->getValue('include_admin_user') == 1 && $this->currentUser()->id() != 1) {
      array_push($uids, 1);
    }

    // Construct the batch data array that will be used in the batch process.
    $batch_data = [
      'uids' => $uids,
      'notify_active_users' => $form_state->getValue('notify_active_users'),
      'notify_blocked_users' => $form_state->getValue('notify_blocked_users'),
      'roles' => $roles,
    ];

    // Store batch data in private tempstore.
    $tempstore = \Drupal::service('tempstore.private')->get('mass_pwreset');
    $tempstore->set('batch_data', $batch_data);

    // Redirect to the confirm form.
    $form_state->setRedirect('mass_pwreset_confirm_form');
  }

}
