<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Senator Dashboard dynamic menu block.
 */
#[Block(
  id: 'senator_dashboard_menu_block',
  admin_label: new TranslatableMarkup('Senator Dashboard menu block')
)]
class SenatorDashboardMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The managed senators handler service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler
   */
  protected ManagedSenatorsHandler $managedSenatorsHandler;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * Constructs the SenatorDashboardMenuBlock object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user's account proxy.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\nys_senator_dashboard\Service\ManagedSenatorsHandler $managed_senators_handler
   *   The managed senators handler service.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ManagedSenatorsHandler $managed_senators_handler,
    MenuLinkTreeInterface $menu_link_tree,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->managedSenatorsHandler = $managed_senators_handler;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('nys_senator_dashboard.managed_senators_handler'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'header_menu',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Prepare menu data, depending on plugin "mode".
    $active_senator_links = match($this->configuration['mode']) {
      'header_menu' => $this->getActiveSenatorLinks(),
      default => [],
    };
    $manage_senator_menu = match($this->configuration['mode']) {
      'header_menu' => $this->getMenuRenderArray('manage_senator'),
      'manage_senator_menu' => $this->getMenuRenderArray('manage_senator', TRUE),
      default => [],
    };
    $constituent_activity_menu = match($this->configuration['mode']) {
      'header_menu' => $this->getMenuRenderArray('constituent_activity'),
      'constituent_activity_menu' => $this->getMenuRenderArray('constituent_activity', TRUE),
      default => [],
    };

    // Build full render array, including block mode variable.
    $return_data = [
      'active_senator_menu' => [
        '#theme' => 'nys_senator_dashboard__set_active_senator_menu',
        '#active_senator_links' => $active_senator_links,
        '#cache' => [
          'contexts' => ['user'],
          'tags' => [
            'user:' . $this->currentUser->id(),
            'tempstore_user:' . $this->currentUser->id(),
          ],
        ],
      ],
      'manage_senator_menu' => $manage_senator_menu,
      'constituent_activity_menu' => $constituent_activity_menu,
    ];
    foreach ($return_data as &$menu) {
      $menu['#mode'] = $this->configuration['mode'];
    }

    return $return_data;
  }

  /**
   * Prepares Active Senator menu link.
   *
   * @return array
   *   The data to render the links.
   */
  private function getActiveSenatorLinks(): array {
    $managed_senators = $this->managedSenatorsHandler->getManagedSenators(FALSE);
    $active_senator_tid = $this->managedSenatorsHandler->getActiveSenator();
    if (empty($managed_senators) || empty($active_senator_tid)) {
      return [];
    }

    $active_senator_links = [];
    foreach ($managed_senators as $senator) {
      $active_senator_links[] = [
        'label' => $senator->label(),
        'url' => Url::fromRoute(
          'nys_senator_dashboard.active_senator.set',
          ['senator_tid' => $senator->id()]
        ),
        'is_active' => ($active_senator_tid == $senator->id()),
        'homepage_url' => $this->managedSenatorsHandler->getActiveSenatorHomepageUrl(),
      ];
    }

    return $active_senator_links;
  }

  /**
   * Get the render array for the Senator Dashboard menu.
   *
   * @param string $sub_menu
   *   Optionally limit to 'manage_senator_menu' or 'constituent_activity_menu'.
   * @param bool $include_description
   *   Optionally include the sub_menu's link descriptions.
   *
   * @return array
   *   The menu render array.
   */
  private function getMenuRenderArray(string $sub_menu = '', bool $include_description = FALSE): array {
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('senator_dashboard' . ($sub_menu ? ".$sub_menu" : ''));
    $tree = $this->menuLinkTree->load('senator-dashboard', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $transformed_tree = $this->menuLinkTree->transform($tree, $manipulators);
    $render_array = $this->menuLinkTree->build($transformed_tree);

    if ($include_description) {
      if (isset($render_array['#items']) && is_array($render_array['#items'])) {
        $this->attachDescriptionsRecursively($render_array['#items']);
      }
    }

    return $render_array;
  }

  /**
   * Recursively find and attach descriptions to all menu items.
   *
   * @param array $items
   *   Reference to menu items.
   *
   * @return void
   *   Data updates are done directly to passed $items array.
   */
  private function attachDescriptionsRecursively(array &$items): void {
    foreach ($items as &$item) {
      if (is_array($item) && isset($item['original_link']) && $item['original_link']->getDescription()) {
        $item['description'] = $item['original_link']->getDescription();
      }
      if (is_array($item)) {
        $this->attachDescriptionsRecursively($item);
      }
    }
  }

}
