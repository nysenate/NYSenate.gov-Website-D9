<?php

namespace Drupal\nys_senator_dashboard\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller to render the Senator Dashboard Menu Block.
 */
class SenatorDashboardController extends ControllerBase {

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Renders the Senator Dashboard Menu Block.
   *
   * @param bool $menu_mode
   *   The flavor of menu to render.
   *
   * @return array
   *   Render array, or empty array on error.
   */
  public function menuPage(bool $menu_mode): array {
    $variables = [
      'mode' => $menu_mode,
    ];
    try {
      $block = $this->blockManager->createInstance(
        'senator_dashboard_menu_block',
        $variables,
      );
    }
    catch (PluginException) {
      return [];
    }
    return $block->build();
  }

}
