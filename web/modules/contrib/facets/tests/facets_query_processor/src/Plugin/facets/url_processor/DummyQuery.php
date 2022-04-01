<?php

namespace Drupal\facets_query_processor\Plugin\facets\url_processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Query string URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "dummy_query",
 *   label = @Translation("Dummy query"),
 *   description = @Translation("Dummy for testing.")
 * )
 */
class DummyQuery extends QueryString {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $eventDispatcher) {
    // Override the default separator.
    $configuration['separator'] = '||';
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request, $entity_type_manager, $eventDispatcher);
  }

}
