<?php

namespace Drupal\mass_pwreset\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Mass Password Reset Form.
 */
class MassPasswordResetConfirmForm extends ConfirmFormBase {

  /**
   * Private tempstore for the batch process data.
   */
  private $batchData = array();

  /**
   * Construct password reset confirm form object.
   */
  public function __construct() {
    // Get batch data from private tempstore.
    $tempstore = \Drupal::service('tempstore.private')->get('mass_pwreset');
    $this->batchData = $tempstore->get('batch_data');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_pwreset_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm password resets?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to reset the passwords for the roles you selected? This action cannot be undone!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset Passwords');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mass_pwreset.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Display selected roles to user.
    $form['roles'] = [
      '#theme' => 'item_list',
      '#title' => t('Selected Roles'),
      '#items' => $this->batchData['roles'],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Initiate the batch process.
    mass_pwreset_multiple_reset($this->batchData);
  }

}
