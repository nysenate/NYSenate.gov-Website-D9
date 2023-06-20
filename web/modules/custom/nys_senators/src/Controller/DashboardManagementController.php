<?php

namespace Drupal\nys_senators\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\nys_senators\Service\DashboardPageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for nys_dashboard routes.
 */
class DashboardManagementController extends ControllerBase {


  /**
   * NYS Senators Management Page Manager service.
   *
   * @var \Drupal\nys_senators\Service\DashboardPageManager
   */
  protected DashboardPageManager $pageManager;

  /**
   * Constructor.
   */
  public function __construct(DashboardPageManager $pageManager) {
    $this->pageManager = $pageManager;
  }

  /**
   * Service injection stub.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
          $container->get('nys_senators.dashboard.page_manager')
      );
  }

  /**
   * Response for the senators page.
   */
  public function senatorPage($taxonomy_term, $tab = 'overview'): array {
    $page = $this->pageManager->getPage($tab);
    $ret = $page ? $page->getContent($taxonomy_term) : [];
    if ($ret) {
      $ret = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'nys-senators-management-dashboard',
            'nys-senators-management-dashboard-' . $tab,
          ],
        ],
        'tab_content' => $ret,
      ];
    }
    return $ret;
  }

}
