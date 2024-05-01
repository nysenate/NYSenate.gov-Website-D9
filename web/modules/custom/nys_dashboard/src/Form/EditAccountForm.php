<?php

namespace Drupal\nys_dashboard\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\AccountForm;

/**
 * Form to edit a user's account information.
 */
class EditAccountForm extends AccountForm {

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    $current_user = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser()->id());
    $this->setEntity($current_user);
    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Manipulate 'account' fieldset.
    $account_fields_to_disable = ['name', 'pass', 'status', 'roles', 'notify', 'current_pass'];
    foreach ($account_fields_to_disable as $field) {
      $form['account'][$field]['#access'] = FALSE;
    }
    $form['account']['mail']['#description'] = '<a href="/user/password">' . $this->t('Reset password') . '</a>';

    return $form;
  }

}
