<?php

namespace Drupal\hacked\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hacked\hackedProject;

/**
 * Controller routines for hacked routes.
 */
class HackedController extends ControllerBase {

  /**
   * @param \Drupal\hacked\hackedProject $project
   * @return array
   */
  public function hackedProject(hackedProject $project) {
    return [
      '#theme' => 'hacked_detailed_report',
      '#project' => $project->compute_details()
    ];
  }

  /**
   * Menu title callback for the hacked details page.
   */
  public function hackedProjectTitle(hackedProject $project) {
    return $this->t('Hacked status for @project', ['@project' => $project->title()]);
  }

  /**
   * Page callback to build up a full report.
   */
  public function hackedStatus() {
    // We're going to be borrowing heavily from the update module
    $build = ['#theme' => 'update_report'];
    if ($available = update_get_available(TRUE)) {
      $build = ['#theme' => 'hacked_report'];
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $data = update_calculate_project_data($available);
      $build['#data'] = $this->getProjectData($data);
      if (!is_array($build['#data'])) {
        return $build['#data'];
      }
    }
    return $build;
  }

  /**
   * Page callback to rebuild the hacked report.
   */
  public function hackedStatusManually() {
    // We're going to be borrowing heavily from the update module
    if ($available = update_get_available(TRUE)) {
      $this->moduleHandler()->loadInclude('update', 'compare.inc');
      $data = update_calculate_project_data($available);
      return $this->getProjectData($data, TRUE, 'admin/reports/hacked');
    }
    return $this->redirect('hacked.report');
  }

  /**
   * Compute the report data for hacked.
   *
   * @param            $projects
   * @param bool|FALSE $force
   * @param null       $redirect
   * @return mixed
   */
  protected function getProjectData($projects, $force = FALSE, $redirect = NULL) {
    // Try to get the report form cache if we can.
    $cache = \Drupal::cache(HACKED_CACHE_TABLE)->get('hacked:full-report');
    if (!empty($cache->data) && !$force) {
      return $cache->data;
    }

    // Enter a batch to build the report.
    $operations = [];
    foreach ($projects as $project) {
      $operations[] = [
        'hacked_build_report_batch',
        [$project['name']],
      ];
    }

    $batch = array(
      'operations' => $operations,
      'finished' => 'hacked_build_report_batch_finished',
      'file' => drupal_get_path('module', 'hacked') . '/hacked.report.inc',
      'title' => t('Building report'),
    );

    batch_set($batch);
    // End page execution and run the batch.
    return batch_process($redirect);
  }

}
