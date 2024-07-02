<?php

namespace Drupal\entityqueue;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the translation handler for subqueues.
 */
class EntitySubqueueTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  protected function entityFormDeleteTranslationUrl(EntityInterface $entity, $form_langcode) {
    $url = parent::entityFormDeleteTranslationUrl($entity, $form_langcode);

    $url->setRouteParameter('entity_queue', $entity->bundle());

    return $url;
  }

}
