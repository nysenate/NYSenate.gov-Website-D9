<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Html;
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
  id: 'nys_senator_dashboard_menu_block',
  admin_label: new TranslatableMarkup('NYS Senator Dashboard: Senator Dashboard menu block')
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
  public function getCacheContexts(): array {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [
      'user:' . $this->currentUser->id(),
      'tempstore_user:' . $this->currentUser->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Prepare menu data, depending on plugin "mode" and user role.
    $active_senator_links = match($this->configuration['mode']) {
      'header_menu' => $this->getActiveSenatorLinks(),
      default => [],
    };
    $manage_senator_menu = [];
    if (!in_array('legislative_correspondent', $this->currentUser->getRoles())) {
      $manage_senator_menu = match($this->configuration['mode']) {
        'header_menu' => $this->getMenuRenderArray('manage_senator'),
        'manage_senator_menu' => $this->getMenuRenderArray('manage_senator', TRUE),
        default => [],
      };
    }
    $constituent_activity_menu = match($this->configuration['mode']) {
      'header_menu' => $this->getMenuRenderArray('constituent_activity'),
      'constituent_activity_menu' => $this->getMenuRenderArray('constituent_activity', TRUE),
      default => [],
    };

    // Build the full render array of menus.
    $return_data = [
      'active_senator_menu' => [
        '#theme' => 'nys_senator_dashboard__set_active_senator_menu',
        '#items' => $active_senator_links,
      ],
      'manage_senator_menu' => $manage_senator_menu,
      'constituent_activity_menu' => $constituent_activity_menu,
    ];

    // Set the mode for each menu.
    foreach ($return_data as &$menu) {
      $menu['#mode'] = $this->configuration['mode'];
    }

    return $return_data;
  }

  /**
   * Prepares active managed senator menu links.
   *
   * @return array
   *   The data to render the links.
   */
  private function getActiveSenatorLinks(): array {
    $managed_senators = $this->managedSenatorsHandler->getManagedSenators(FALSE);
    if (empty($managed_senators)) {
      return [];
    }

    // Build active managed senator switcher links.
    $active_senator_tid = $this->managedSenatorsHandler->ensureAndGetActiveSenator();
    $active_senator_links = [];
    foreach ($managed_senators as $senator) {
      $active_senator_links[] = [
        'label' => $senator->label(),
        'title' => $senator->label(),
        'url' => Url::fromRoute(
          'nys_senator_dashboard.active_senator.set',
          ['senator_tid' => $senator->id()]
        ),
        'is_active' => ($active_senator_tid == $senator->id()),
        'homepage_url' => $this->managedSenatorsHandler->getActiveSenatorHomepageUrl(),
        'attributes' => new Attribute(),
      ];
    }

    // Process the links to create a proper menu structure.
    $items = [];
    $active_link = [];
    $additional_links = [];
    foreach ($active_senator_links as $link) {
      if ($link['is_active']) {
        $active_link = $link;
        $active_link['is_expanded'] = FALSE;
        $active_link['is_collapsed'] = FALSE;
        $active_link['in_active_trail'] = FALSE;
        $active_link['icon'] = 'briefcase';
      }
      else {
        $additional_links[] = $link;
      }
    }
    if (!empty($additional_links)) {
      $active_link['is_expanded'] = TRUE;
      $active_link['below'] = $additional_links;
      $active_link['url'] = Url::fromRoute('<button>');
    }
    else {
      $active_link['url'] = Url::fromUri($active_link['homepage_url']);
    }
    $items[] = $active_link;

    // Process the menu items to meet nys:menu requirements.
    $this->prepareMenuItems($items);

    return $items;
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
    $mode = $this->configuration['mode'];
    $tree = $this->menuLinkTree->load('senator-dashboard', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $transformed_tree = $this->menuLinkTree->transform($tree, $manipulators);
    $render_array = $this->menuLinkTree->build($transformed_tree);
    $render_array['#theme'] = "{$render_array['#theme']}__{$mode}";

    // @todo Re-implement as menu link tree manipulators?
    if (isset($render_array['#items'])) {
      if ($mode === 'header_menu') {
        $this->processHeaderMenu($render_array['#items']);
      }
      elseif (
        ($mode === 'manage_senator_menu' && $sub_menu === 'manage_senator')
        || ($mode === 'constituent_activity_menu' && $sub_menu === 'constituent_activity')
      ) {
        $render_array['#items'] = $this->prepareActivityMenuItems($render_array['#items'], 'senator_dashboard.' . $sub_menu);
      }
      $this->prepareMenuItems($render_array['#items']);
      $this->attachDescriptionsRecursively($render_array['#items']);
    }

    return $render_array;
  }

  /**
   * Process the header menu items.
   *
   * @param array $items
   *   The menu items to process.
   */
  private function processHeaderMenu(array &$items): void {
    // Find the items that are overview pages.
    $find_overview = function (&$items) {
      foreach ($items as $key => &$item) {
        if (str_ends_with($key, '.overview')) {
          $item['attributes']->addClass('c-menu--item-overview');
        }
      }
    };

    // Strip out dropdown for nested management sections.
    $setup_mega_menu = function (&$items) {
      foreach ($items as &$item) {
        if ($item['below']) {
          $item['is_expanded'] = FALSE;
          $item['url'] = Url::fromRoute('<nolink>');
        }
      }
    };

    foreach ($items as $key => &$item) {
      // Set icons for primary level items.
      $item['icon'] = match ($key) {
        'senator_dashboard.manage_senator' => 'file-pen',
        'senator_dashboard.constituent_activity' => 'users',
        default => '',
      };

      // Set class for mega menu in dashboard.
      if ($key == 'senator_dashboard.manage_senator') {
        $item['attributes']->addClass('c-menu__item-mega');
        $setup_mega_menu($item['below']);
      }

      if ($item['below']) {
        $item['url'] = Url::fromRoute('<button>');
        $find_overview($item['below']);
      }
    }
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

  /**
   * Process menu items to add attributes and classes needed for menu SDC.
   *
   * @param array $items
   *   An array of menu items.
   */
  private function prepareMenuItems(array &$items): void {
    foreach ($items as &$item) {
      $link_attributes = $item['url']->getOption('attributes') ?? [];

      if (isset($link_attributes['class']) && is_string($link_attributes['class'])) {
        $link_attributes['class'] = explode(' ', $link_attributes['class']);
      }

      $link_attributes['class'][] = 'c-menu__link';

      // Add the data-plugin-id attribute based on the menu link's plugin ID.
      // This is utilized in the menu components.
      if (isset($item['original_link'])) {
        $plugin_id = $item['original_link']->getPluginId();
        $link_attributes['data-plugin-id'] = Html::getUniqueId($plugin_id);
      }

      $item['url']->setOption('attributes', $link_attributes);

      // Set classes for the link wrapper.
      $item_classes = ['c-menu__item'];

      if ($item['is_expanded'] ?? FALSE) {
        $item_classes[] = 'c-menu__item-expanded';
      }

      if ($item['is_collapsed'] ?? FALSE) {
        $item_classes[] = 'c-menu__item-collapsed';
      }

      if ($item['in_active_trail'] ?? FALSE) {
        $item_classes[] = 'c-menu__item-active-trail';
      }

      $item['attributes']->addClass($item_classes);

      if (!empty($item['below'])) {
        $this->prepareMenuItems($item['below']);
      }
    }
  }

  /**
   * Manipulate the data to only show the manage senator links.
   *
   * Manipulate the data to only show the manage senator links. This is making
   * several assumptions. The top level is the container for the overall menu.
   * This is because the menu would likely contain other sections. Below that
   * there is an overview link we don't want. We'll only need the other items.
   *
   * @param array $items
   *   The menu items.
   * @param string $parent
   *   The menu route items to clean up.
   *
   * @return array
   *   The cleaned up menu items.
   */
  private function prepareActivityMenuItems(array $items = [], string $parent = ''): array {
    $items = $items[$parent]['below'];
    foreach ($items as $key => $item) {
      if (str_ends_with($key, '.overview')) {
        unset($items[$key]);
      }
    }
    return $items;
  }

}
