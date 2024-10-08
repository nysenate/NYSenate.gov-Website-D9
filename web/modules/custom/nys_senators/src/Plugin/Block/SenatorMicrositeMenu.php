<?php

namespace Drupal\nys_senators\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_users\UsersHelper;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The Entity TypeManager Interfacee.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $routeMatch;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('cache.default'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CacheBackendInterface $cache_backend,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentRouteMatch $route_match,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The menu links array for the 'Senator Microsite Menu' template.
   */
  public function build() {
    /**
     * @var \Drupal\node\Entity\Node $node
     */
    $node = $this->routeMatch->getParameter('node');

    $microsite_pages = [
      'microsite_page',
      'article',
      'event',
      'honoree',
      'in_the_news',
      'petition',
      'webform',
      'video',
    ];
    if ($node instanceof NodeInterface && in_array($node->getType(), $microsite_pages)) {
      $senator_terms = ($node->hasField('field_senator_multiref') && !$node->get('field_senator_multiref')->isEmpty()) ? $node->get('field_senator_multiref')->getValue() : [];
      $tids = [];
      foreach ($senator_terms as $tid) {
        $tids[] = (int) $tid['target_id'];
      }
      $nids = &drupal_static(__FUNCTION__);
      try {
        $node_storage = $this->entityTypeManager->getStorage('node');
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('nys_senators')->error("Failed to render nys_senators_microsite_menu block due to missing node module.");
        return [];
      }
      if (!isset($nids)) {
        // Get all 'Microsite Pages' with the same senator reference.
        $nids = $node_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 1)
          ->condition('type', 'microsite_page')
          ->condition("field_senator_multiref.%delta.target_id", $tids, 'IN')
          ->condition("field_senator_multiref.%delta", [0], 'IN')
          ->execute();
      }
      $nodes = $node_storage->loadMultiple($nids);
      $menu_links = [];
      /**
       * @var \Drupal\node\Entity\Node $node
       */
      foreach ($nodes as $node) {
        /**
         * @var \Drupal\taxonomy\Entity\Term $term
         */
        $term = $node->get('field_microsite_page_type')->entity ?? [];
        if ($term instanceof TermInterface) {
          $menu_title = $term->getName() ?? '';
          if (!$term->get('field_microsite_menu_weight')->isEmpty()) {
            $menu_weight = $term->get('field_microsite_menu_weight')->value;
            // Get the url alias for each 'Microsite Page' and populate
            // links for menu block.
            $menu_links[$menu_weight]['menu_url'] = $node->toUrl()->toString();
            $menu_links[$menu_weight]['menu_title'] = $menu_title;
          }
        }
      }
      ksort($menu_links);
      $current_user = UsersHelper::resolveUser();
      return [
        '#theme' => 'senator_microsite_menu_block',
        '#menu_links' => $menu_links,
        '#is_logged' => $current_user->isAuthenticated(),
        '#user_first_name' => $current_user->field_first_name?->value ?? 'Guest',
      ];
    }
    return [];
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
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nys_senators_microsite_menu'] = $form_state->getValue('nys_senators_microsite_menu');
  }

}
