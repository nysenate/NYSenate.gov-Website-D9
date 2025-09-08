<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders bills using the senator_dashboard_bill_teaser display.
 */
#[Block(
  id: 'nys_senator_dashboard_bill_teaser',
  admin_label: new TranslatableMarkup('NYS Senator Dashboard: Bill Teaser Block'),
  context_definitions: [
    'node' => new EntityContextDefinition(
      'entity:node',
      label: new TranslatableMarkup('Node'),
      required: FALSE
    ),
  ]
)]
class SenatorDashboardBillTeaserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new SenatorDashboardBillTeaserBlock instance.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route Match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SenatorDashboardBillTeaserBlock {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['route'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    try {
      $nid = $this->getContextValue('node')?->id() ?? $this->routeMatch->getParameter('arg_0');
    }
    catch (\Exception) {
      return $build;
    }
    if (empty($nid)) {
      return $build;
    }

    try {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if (!$node || $node->bundle() !== 'bill') {
        return $build;
      }
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $build = $view_builder->view($node, 'senator_dashboard_bill_teaser');
    }
    catch (\Exception) {
      return $build;
    }

    return $build;
  }

}
