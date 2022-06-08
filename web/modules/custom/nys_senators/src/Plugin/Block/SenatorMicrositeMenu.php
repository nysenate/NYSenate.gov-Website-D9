<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\Entity\Node;

/**
 * Block to generate the menu for all of the Senator Microsite Pages.
 *
 * @Block(
 *   id = "nys_senators_microsite_menu",
 *   admin_label = @Translation("Senator Microsite Menu"),
 *   category = @Translation("NYS Senators"),
 * )
 */
class SenatorMicrositeMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The CacheBackend Interface.
   *
   * @var Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The Entity TypeManager Interfacee.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Current Route Match.
   *
   * @var Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('cache.default'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'));
  }

  /**
   * Constructor.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
  CacheBackendInterface $cache_backend,
                              EntityTypeManagerInterface $entity_type_manager,
  CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->routeMatch->getParameter('node');
    if (!empty($node) && $node->getType() == 'microsite_page') {
      $senator_terms = $node->get('field_senator_multiref')->getValue();
      $tids = [];
      foreach ($senator_terms as $tid) {
        $tids[] = (int) $tid['target_id'];
      }
      // Get all 'Microsite Pages' with the same senator reference.
      $nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('field_senator_multiref', $tids)
        ->execute();
      $nodes = Node::loadMultiple($nids);
      $menu_links = [];
      foreach ($nodes as $node) {
        $term = $node->get('field_microsite_page_type')->entity;
        $menu_title = $term->getName();
        $menu_weight = $term->get('field_microsite_menu_weight')->value;
        // Get the url alias for each 'Microsite Page' and populate
        // links for menu block.
        $menu_links[$menu_weight]['menu_url'] = $node->toUrl()->toString();
        $menu_links[$menu_weight]['menu_title'] = $menu_title;
      }
      return [
        '#theme' => 'senator_microsite_menu_block',
        '#menu_links' => $menu_links,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nys_senators_microsite_menu'] = $form_state->getValue('nys_senators_microsite_menu');
  }

}
