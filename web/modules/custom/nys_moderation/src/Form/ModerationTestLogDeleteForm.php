<?php

namespace Drupal\nys_moderation\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\nys_moderation\Entity\ModerationTestLogItem;

/**
 * Confirmation form for moderation test logs (content)
 */
class ModerationTestLogDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritDoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Delete test log?');
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Reproduction of these results may not be possible.  Are you sure you want to delete them?');
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.moderation_test_log.collection');
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('entity.moderation_test_log.collection');
    try {
      // First delete all the individual log items.
      // Avoiding loadMultiple().
      $ids = $this->entityTypeManager->getStorage('moderation_test_log_item')
        ->getQuery()
        ->condition('log_id', $this->entity->id())
        ->accessCheck()
        ->execute();
      foreach ($ids as $id) {
        $item = ModerationTestLogItem::load($id);
        $item->delete();
      }

      // Finally delete the log thread.
      $this->entity->delete();
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Could not delete!  @msg', ['@msg' => $e->getMessage()]));
    }
  }

}
