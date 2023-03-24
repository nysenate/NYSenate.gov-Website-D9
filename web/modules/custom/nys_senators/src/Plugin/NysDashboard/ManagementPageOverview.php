<?php

namespace Drupal\nys_senators\Plugin\NysDashboard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\nys_senators\ManagementPageInterface;
use Drupal\nys_senators\Service\OverviewStatsManager;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates the overview page for the senator management dashboard.
 *
 * @SenatorManagementPage(
 *   id = "overview"
 * )
 */
class ManagementPageOverview extends ManagementPageBase {

  /**
   * NYS Senators Overview Stats Manager service.
   *
   * @var \Drupal\nys_senators\Service\OverviewStatsManager
   */
  protected OverviewStatsManager $stats;

  /**
   * {@inheritDoc}
   *
   * Adds the overview stats manager.
   */
  public function __construct(EntityTypeManagerInterface $manager, OverviewStatsManager $stats, $plugin_id, $definition, array $configuration) {
    parent::__construct($manager, $plugin_id, $definition, $configuration);
    $this->stats = $stats;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ManagementPageInterface {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('nys_senators.dashboard.stats_manager'),
      $plugin_id,
      $plugin_definition,
      $configuration
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getContent(TermInterface $senator): array {

    return [
      'stats' => [
        '#theme' => 'nys_senators_management_overview_stats',
        '#attached' => ['library' => ['nys_senators/nys_senators_management']],
        '#stats' => $this->stats->getStats($senator),
        '#title' => 'Overview',
      ],
      'active_list' => views_embed_view('upcoming_legislation', 'session_active'),
    ];

  }

}
