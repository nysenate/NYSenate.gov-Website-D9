<?php

namespace Drupal\entityqueue\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides the entity subqueue delete confirmation form.
 */
class EntitySubqueueDeleteForm extends ContentEntityDeleteForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\entityqueue\EntitySubqueueInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the parent queue entity.
    return $this->entity->getQueue()->toUrl('subqueue-list');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
