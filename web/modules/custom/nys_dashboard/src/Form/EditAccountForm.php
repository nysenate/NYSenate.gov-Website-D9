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

    // Attach library.
    $form['#attached']['library'][] = 'nysenate_theme/edit-account';

    // Manipulate 'account' fieldset.
    $account_fields_to_disable = ['name', 'pass', 'status', 'roles', 'notify'];
    foreach ($account_fields_to_disable as $field) {
      $form['account'][$field]['#access'] = FALSE;
    }
    $form['account']['current_pass']['#description']
      = $this->t('Required if you want to change your email address.')
      . ' <a href="/user/password">' . $this->t('Reset password') . '</a>';

    // Format address field.
    $form['field_address']['widget'][0]['address']['#after_build'][] = '::formatAddressField';

    // Format date of birth field.
    $form["field_dateofbirth"]["widget"][0]['value']['#after_build'][] = '::formatDobField';

    // Format receive emails field.
    $form['field_user_receive_emails']['widget']['value']['#title'] = $this->t('Receive emails from my Senator');
    unset($form['field_user_receive_emails']['widget']['value']['#description']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Your account has been successfully updated.'));
    return parent::save($form, $form_state);
  }

  /**
   * Callback to format address field.
   */
  public function formatAddressField($element, $form_state) {
    $element['address_line2']['#title'] = $this->t('Apt/suite/floor (optional)');
    $element['address_line2']['#title_display'] = 'before';
    $element['address_line3']['#access'] = FALSE;
    return $element;
  }

  /**
   * Callback to format date of birth field.
   */
  public function formatDobField($element, $form_state) {
    unset($element['#theme']);
    unset($element['#theme_wrappers']);
    $element['date']['#title'] = $this->t('Date of birth');
    $element['date']['#title_display'] = 'before';
    return $element;
  }

}
