<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senators\Event\OverviewStatsAlterEvent;
use Drupal\nys_senators\SenatorsHelper;
use Drupal\nys_senators\Service\OverviewStatsManager;
use Drupal\nys_users\UsersHelper;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Returns responses for nys_dashboard routes.
 */
class SenatorsDashboardController extends ControllerBase {

  /**
   * Drupal's Event Dispatcher service.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * NYS Senators Overview Stats Manager service.
   *
   * @var \Drupal\nys_senators\Service\OverviewStatsManager
   */
  protected OverviewStatsManager $manager;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $dispatcher, OverviewStatsManager $manager) {
    $this->dispatcher = $dispatcher;
    $this->manager = $manager;
  }

  /**
   * Service injection stub.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('nys_senators.dashboard.stats_manager')
    );
  }

  /**
   * Response for the "Senator Management" tab on the user dashboard.
   *
   * Displays links to the management page of each senator to which the current
   * user has LC/MCP access. The list is sorted by "<last_name> <first_name>".
   */
  public function senatorManagement(): array {

    // Get the User entity for the current user.
    $user = UsersHelper::resolveUser($this->currentUser());

    // Collect the senator references based on user permissions.
    $senators = [];
    $to_add = [
      'isMcp' => 'field_senator_multiref',
      'isLc' => 'field_senator_inbox_access',
    ];
    foreach ($to_add as $perm => $field) {
      if (UsersHelper::{$perm}($user)) {
        $senators = array_merge(
          $senators,
          $user->{$field}->referencedEntities() ?? []
        );
      }
    }
    SenatorsHelper::sortByName($senators);

    $content = [];
    $viewer = $this->entityTypeManager()->getViewBuilder('taxonomy_term');
    /** @var \Drupal\taxonomy\Entity\Term $senator */
    foreach ($senators as $senator) {
      $content['senator_' . $senator->id()] = [
        '#attributes' => ['class' => ['senator_management_link']],
        '#type' => 'container',
        'senator' => $viewer->view($senator, 'sponsor_list'),
      ];
    }

    return $content;
  }

  /**
   * Response for the senators page.
   */
  public function senatorPage($taxonomy_term, $tab = 'overview'): array {

    $content['senator_management_dashboard'] = method_exists($this, $tab)
      ? $this->$tab($taxonomy_term)
      : [];

    return $content;
  }

  /**
   * Creates the Overview tab on the Senator dashboard.
   */
  protected function overview(TermInterface $senator): array {
    $stats = $this->manager->getStats($senator);

    $event = new OverviewStatsAlterEvent($stats);
    $this->dispatcher->dispatch($event);

    return [
      'stats' => [
        '#theme' => 'nys_senators_management_overview_stats',
        '#attached' => ['library' => ['nys_senators/nys_senators_management']],
        '#stats' => $event->stats,
        '#title' => 'Overview',
      ],
      'active_list' => views_embed_view('upcoming_legislation', 'session_active'),
    ];
  }

}
