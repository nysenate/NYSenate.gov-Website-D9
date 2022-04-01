<?php

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\hacked\hackedProject;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateFetcherInterface;

/**
 * Batch callback to build the hacked report.
 */
function hacked_build_report_batch($project_name, &$context) {
  if (!isset($context['results']['report'])) {
    $context['results']['report'] = array();
  }
  \Drupal::moduleHandler()
    ->loadInclude('hacked', 'inc', 'includes/hacked_project');
  $project = new hackedProject($project_name);
  $context['results']['report'][$project_name] = $project->compute_report();
  $context['message'] = t('Finished processing: @name', array('@name' => $project->title()));
}

/**
 * Completion callback for the report batch.
 *
 * @param $success
 * @param $results
 */
function hacked_build_report_batch_finished($success, $results) {
  if ($success) {
    // Sort the results.
    usort($results['report'], '_hacked_project_report_sort_by_status');
    // Store them.
    \Drupal::cache(HACKED_CACHE_TABLE)
      ->set('hacked:full-report', $results['report'], strtotime('+1 day'));
    \Drupal::state()->set('hacked.last_check', time());
  }
}

/**
 * Implements hook_preprocess_update_last_check().
 */
function hacked_preprocess_update_last_check(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route == 'hacked.report') {
    $variables['link'] = $variables['link'] = Link::fromTextAndUrl(t('Check manually'), new Url('hacked.manual_status', array(), array(
      'query' => \Drupal::destination()
        ->getAsArray()
    )));
  }
}

/**
 * Prepares variables for hacked project status templates.
 *
 * Default template: hacked-project-status.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - project: An array of information about the project.
 */
function hacked_preprocess_update_project_status(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route == 'hacked.report') {

    // Storing by reference because we are sorting the project values.
    $project = &$variables['project'];

    $variables['install_type'] = $project['install_type'];
    if ($project['install_type'] == 'dev' && !empty($project['datestamp'])) {
      $variables['datestamp'] = Drupal::service('date.formatter')
        ->format($project['datestamp'], 'custom', 'Y-M-d');
    }

    $variables['existing_version'] = $project['existing_version'];

    $variables['versions'] = [
      [
        '#theme'   => 'hacked_project_summary',
        '#project' => $project,
      ]
    ];

    switch ($project['status']) {
      case HACKED_STATUS_UNHACKED:
        $uri = 'core/misc/icons/73b355/check.svg';
        $text = t('Unchanged');
        $project['status'] = UpdateManagerInterface::CURRENT;
        break;
      case HACKED_STATUS_HACKED:
        $uri = 'core/misc/icons/e32700/error.svg';
        $text = t('Changed!');
        $project['status'] = UpdateFetcherInterface::NOT_CHECKED;
        break;
      case HACKED_STATUS_UNCHECKED:
      default:
        $uri = 'core/misc/icons/e29700/warning.svg';
        $text = t('Unchecked');
        $project['status'] = UpdateFetcherInterface::NOT_CHECKED;
        break;
    }

    $variables['status']['label'] = $text;
    $variables['status']['icon'] = [
      '#theme'  => 'image',
      '#width'  => 18,
      '#height' => 18,
      '#uri'    => $uri,
      '#alt'    => $text,
      '#title'  => $text,
    ];
  }
}

/**
 * Prepares variables for hacked status report templates.
 *
 * Default template: hacked-report.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data: An array of data about each project's status.
 */
function template_preprocess_hacked_report(&$variables) {
  $data = $variables['data'];

  $last = \Drupal::state()->get('hacked.last_check') ?: 0;

  $variables['last_checked'] = [
    '#theme'    => 'update_last_check',
    '#last'     => $last,
    // Attach the library to a variable that gets printed always.
    '#attached' => [
      'library' => [
        'update/drupal.update.admin',
      ]
    ],
  ];

  // For no project update data, populate no data message.
  if (empty($data)) {
    $destination = \Drupal::destination()->getAsArray();
    $variables['no_updates_message'] = t('No hacked information available. <a href=":check_manually">check manually</a>.', array(
      ':run_cron'       => Url::fromRoute('system.run_cron', [], ['query' => $destination]),
      ':check_manually' => Url::fromRoute('hacked.manual_status', [], ['query' => $destination]),
    ));
  }

  $rows = [];

  foreach ($data as $project) {
    if (!isset($project['status'])) {
      continue;
    }

    $project_status = [
      '#theme'   => 'update_project_status',
      '#project' => $project,
    ];

    // Build project rows.
    if (!isset($rows[$project['project_type']])) {
      $rows[$project['project_type']] = [
        '#type'       => 'table',
        '#attributes' => ['class' => ['update']],
      ];
    }
    $row_key = !empty($project['title']) ? mb_strtolower($project['title']) : mb_strtolower($project['name']);

    // Add the project status row and details.
    $rows[$project['project_type']][$row_key]['status'] = $project_status;

    // Add project status class attribute to the table row.
    switch ($project['status']) {
      case HACKED_STATUS_UNHACKED:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-success']];
        break;
      case HACKED_STATUS_HACKED:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-error']];
        break;
      case HACKED_STATUS_UNCHECKED:
      default:
        $rows[$project['project_type']][$row_key]['#attributes'] = ['class' => ['color-warning']];
        break;
    }
  }

  $project_types = [
    'core'            => t('Drupal core'),
    'module'          => t('Modules'),
    'theme'           => t('Themes'),
    'module-disabled' => t('Uninstalled modules'),
    'theme-disabled'  => t('Uninstalled themes'),
  ];

  $variables['project_types'] = [];
  foreach ($project_types as $type_name => $type_label) {
    if (!empty($rows[$type_name])) {
      ksort($rows[$type_name]);
      $variables['project_types'][] = [
        'label' => $type_label,
        'table' => $rows[$type_name],
      ];
    }
  }
}

/**
 * @param $variables
 */
function template_preprocess_hacked_project_summary(&$variables) {
  $project = $variables['project'];

  $changes[] = \Drupal::translation()
    ->formatPlural($project['counts']['different'], '1 file changed', '@count files changed');
  $changes[] = \Drupal::translation()
    ->formatPlural($project['counts']['missing'], '1 file deleted', '@count files deleted');
  if ($project['counts']['access_denied'] > 0) {
    $changes[] = \Drupal::translation()
      ->formatPlural($project['counts']['access_denied'], '1 unreadable file', '@count unreadable files');
  }
  $variables['changes'] = implode(', ', $changes);

  $variables['link'] = Url::fromRoute('hacked.project', ['project' => $project['project_name']]);
}

