<?php

namespace Drupal\nys_subscriptions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the subscription entity edit forms.
 *
 * @obsolete This may be obsolete since we're not allowing editing/adding.
 *
 * @todo Remove this?
 */
class SubscriptionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()
        ->addStatus($this->t('New subscription %label has been created.', $message_arguments));
      $this->logger('nys_subscriptions')
        ->notice('Created new subscription %label', $logger_arguments);
    }
    else {
      $this->messenger()
        ->addStatus($this->t('The subscription %label has been updated.', $message_arguments));
      $this->logger('nys_subscriptions')
        ->notice('Updated new subscription %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.subscription.canonical', ['subscription' => $entity->id()]);
  }

}
