<?php

namespace Drupal\password_policy\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * The definition of the password policy form.
 */
class PasswordPolicyFormAdd extends PasswordPolicyForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $status = parent::save($form, $formState);

    if ($status) {
      $this->messenger()->addMessage($this->t('The password policy %label has been added.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The password policy was not saved.'));
    }

    $formState->setRedirect('entity.password_policy.edit_form', [
      'password_policy' => $this->entity->id(),
    ]);
  }

}
