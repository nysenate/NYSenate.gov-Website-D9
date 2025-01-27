<?php

namespace Drupal\nys_senator_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\nys_senator_dashboard\Service\SenatorDashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Senator Dashboard dynamic menu block.
 *
 * @Block(
 *   id = "senator_dashboard_menu_block",
 *   admin_label = @Translation("Senator Dashboard menu block")
 * )
 */
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
   * The entity type manager service.
   *
   * @var \Drupal\nys_senator_dashboard\Service\SenatorDashboardService
   */
  protected $nysSenatorDashboardService;

  /**
   * Constructs the SenatorDashboardMenuBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    SenatorDashboardService $nys_senator_dashboard_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->nysSenatorDashboardService = $nys_senator_dashboard_service;
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
      $container->get('nys_senator_dashboard.service')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $current_user_id = $this->currentUser->id();
    try {
      $current_user = $this->entityTypeManager->getStorage('user')->load($current_user_id);
    }
    catch (\Throwable) {
    }
    $links = [];
    if (
      isset($current_user)
      && $current_user->hasField('field_senator_multiref')
    ) {
      $managed_senators = $current_user->field_senator_multiref->referencedEntities();
      foreach ($managed_senators as $senator) {
        // If no active senator, set first in list as active.
        $active_senator_id = $this->nysSenatorDashboardService->getActiveSenatorForCurrentUser();
        if (!$active_senator_id) {
          $this->nysSenatorDashboardService->setActiveSenatorForUserId($current_user_id, $senator->id(), FALSE);
          $active_senator_id = $senator->id();
        }
        $links[] = [
          '#type' => 'link',
          '#title' => $senator->label(),
          '#url' => Url::fromRoute(
            'nys_senator_dashboard.set_managed_senator',
            ['senator_id' => $senator->id()]
          ),
          '#attributes' => [
            'class' => $active_senator_id == $senator->id()
              ? ['is-active-senator']
              : [],
          ],
        ];
      }
    }
    return [
      '#theme' => 'item_list',
      '#items' => $links,
      '#attributes' => ['class' => ['senator-dashboard-menu']],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'user:' . $this->currentUser->id(),
          'taxonomy_vocabulary:senator',
          'tempstore_user:' . $this->currentUser->id(),
        ],
      ],
    ];
  }

}
