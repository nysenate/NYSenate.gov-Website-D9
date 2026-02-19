<?php

namespace Drupal\nys_moderation\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Confirmation form for moderation test sets (config)
 */
class ModerationTestSetDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritDoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Delete test set?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.moderation_test_set.collection');
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.moderation_test_set.collection');
    try {
      $this->entity->delete();
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Could not delete!  @msg', ['@msg' => $e->getMessage()]));
    }
  }

}
