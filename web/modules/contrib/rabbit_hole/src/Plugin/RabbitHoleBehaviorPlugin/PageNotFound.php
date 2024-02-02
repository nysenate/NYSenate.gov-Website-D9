<?php

namespace Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Denies access to a page.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "page_not_found",
 *   label = @Translation("Page not found")
 * )
 */
class PageNotFound extends RabbitHoleBehaviorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $current_response = NULL) {
    throw new NotFoundHttpException();
  }

}
