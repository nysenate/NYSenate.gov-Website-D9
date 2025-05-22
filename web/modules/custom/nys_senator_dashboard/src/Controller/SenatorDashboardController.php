<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to manage basic routes for Senator Dashboard.
 */
class SenatorDashboardController extends ControllerBase {

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The senator dashboard helper service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper
   */
  protected SenatorDashboardHelper $senatorDashboardHelper;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\nys_senator_dashboard\Service\SenatorDashboardHelper $senator_dashboard_helper
   *   The senator dashboard helper service.
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    RouteMatchInterface $route_match,
    SenatorDashboardHelper $senator_dashboard_helper,
  ) {
    $this->blockManager = $block_manager;
    $this->routeMatch = $route_match;
    $this->senatorDashboardHelper = $senator_dashboard_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('current_route_match'),
      $container->get('nys_senator_dashboard.senator_dashboard_helper')
    );
  }

  /**
   * Renders the Senator Dashboard Menu Block.
   *
   * @param string $menu_mode
   *   The flavor of menu to render.
   *
   * @return array
   *   Render array, or empty array on error.
   */
  public function menuPage(string $menu_mode): array {
    $variables = [
      'mode' => $menu_mode,
    ];
    try {
      $block = $this->blockManager->createInstance(
        'nys_senator_dashboard_menu_block',
        $variables,
      );
    }
    catch (PluginException) {
      return [];
    }
    return $block->build();
  }

  /**
   * Title callback to return label of contextual entity ID as page title.
   *
   * @return string
   *   The title string
   */
  public function contextualDetailPageTitle() {
    $title = 'Detail page | Constituent Activity';
    $entity = $this->senatorDashboardHelper->getContextualEntity();
    return "{$entity?->label()} | Constituent Activity" ?? $title;
  }

}
