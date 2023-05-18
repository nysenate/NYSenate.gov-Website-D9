<?php

namespace Drupal\nys_senators\Plugin\NysDashboard;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_senators\ManagementPageBase;
use Drupal\nys_senators\ManagementPageInterface;
use Drupal\nys_senators\SenatorsHelper;
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
  public function __construct(EntityTypeManagerInterface $manager, Connection $connection, SenatorsHelper $helper, OverviewStatsManager $stats, $plugin_id, $definition, array $configuration) {
    parent::__construct($manager, $connection, $helper, $plugin_id, $definition, $configuration);
    $this->stats = $stats;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ManagementPageInterface {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('nys_senators.senators_helper'),
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

    // Discover today's session.
    $date = date("Y-m-d");
    $storage = $this->manager->getStorage('node');
    $results = $storage->getQuery()
      ->condition('field_date_range.value', $date . '%', 'LIKE')
      ->condition('type', 'session')
      ->execute();
    if (count($results)) {
      $session = $storage->load(current($results));
    }
    else {
      $session = NULL;
    }
    $active_list = $session
      ? views_embed_view('upcoming_legislation', 'session_active', $session->id())
      : ['#markup' => 'No session was found for ' . $date];
    $active_list['#attributes']['class'][] = 'management-overview-active-list';

    return [
      '#theme' => 'nys_senators_management_overview',
      '#attached' => ['library' => ['nys_senators/nys_senators_management']],
      '#stats' => [
        '#theme' => 'nys_senators_management_overview_stats',
        '#stats' => $this->stats->getStats($senator),
        '#title' => 'Year-to-Date Statistics',
      ],
      '#active_list' => $active_list,
    ];

  }

}
