<?php

namespace Drupal\menu_token\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\token\TreeBuilderInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Class MenuTokenSupportedTokensController.
 *
 * @package Drupal\menu_token\Controller
 */
class MenuTokenSupportedTokensController extends ControllerBase {

  protected $configFactory;
  protected $treeBuilder;
  protected $renderer;

  /**
   * MenuTokenSupportedTokensController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\token\TreeBuilderInterface $treeBuilder
   *   Tree builder service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TreeBuilderInterface $treeBuilder, RendererInterface $renderer) {

    $this->configFactory = $configFactory;
    $this->treeBuilder = $treeBuilder;
    $this->renderer = $renderer;
  }

  /**
   * Controller method for displaying supported token page.
   *
   * @return array
   *   Return the build array.
   */
  public function content() {

    /*
     * Load the configuration from configuration entity
     * and check for available entities.
     */
    $availableEntitiesConfiguration = $this->configFactory->get('menu_token.availableentitiesconfiguration');
    $data = $availableEntitiesConfiguration->getRawData();

    $renderable = [];
    foreach ($data['available_entities'] as $config_key => $config_item) {

      if ($config_item !== 0) {
        $renderable[] = $config_key;
      }
    }

    // Build the token tree for display.
    $token_tree = $this->treeBuilder->buildRenderable($renderable, [
      'click_insert' => FALSE,
      'show_restricted' => FALSE,
      'show_nested' => FALSE,
    ]);

    // Create the html output.
    $output = '<dt>' . t('The list of the currently available tokens supported by menu_token are shown below.') . '</dt>';
    $output .= '<br /><dd>' . $this->renderer->render($token_tree) . '</dd>';
    $output .= '</dl>';

    $build = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    return $build;
  }

  /**
   * When this subscriber is created, it will get the services and store them.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Dependency injection container.
   *
   * @return static
   *   Container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('token.tree_builder'),
      $container->get('renderer')
    );
  }

}
