<?php

namespace Drupal\entity_print\Renderer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_print\PrintEngineException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The RendererFactory class.
 */
class RendererFactory implements RendererFactoryInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function create($item, $context = 'entity') {
    $entity_type_manager = $this->container->get('entity_type.manager');

    // If we get an array or something, just look at the first one.
    if (is_array($item)) {
      $item = array_pop($item);
    }

    if ($item instanceof EntityInterface && $entity_type_manager->hasHandler($item->getEntityTypeId(), 'entity_print')) {
      return $entity_type_manager->getHandler($item->getEntityTypeId(), 'entity_print');
    }

    throw new PrintEngineException(sprintf('Rendering not yet supported for "%s". Entity Print context "%s"', is_object($item) ? get_class($item) : $item, $context));
  }

}
