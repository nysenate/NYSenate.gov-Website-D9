<?php

/**
 * @file
 * Command-line tools to aid performing and developing migrations.
 */

/**
 * Implements hook_drush_command().
 */
function job_scheduler_waiting_drush_command() {
  $items['job-scheduler-waiting-perform'] = [
    'description' => 'Perform the named, waiting the job schedule.',
    'arguments' => [
      'name' => 'Name of the job to perform.',
    ],
    'drupal dependencies' => ['job_scheduler_waiting'],
    'aliases' => ['jswp'],
  ];

  return $items;
}

/**
 * Performs a job.
 */
function drush_job_scheduler_waiting_perform($name = '') {
  if (!$name) {
    drush_set_error('JOB_ERROR', dt('You must specify name of the job.'));
    return;
  }

  job_scheduler_waiting_perform_job($name);
}

/**
 * Performs the named job with no timeout.
 *
 * @param string $name
 *   The name of the job to work with.
 */
function job_scheduler_waiting_perform_job($name) {
  set_time_limit(0);

  while (TRUE) {
    $timer = microtime(TRUE);

    $scheduler = \Drupal::service('job_scheduler.manager');
    $results = $scheduler->perform($name);

    if ($results['total']) {
      $kernel = \Drupal::service('kernel');
      $kernel->rebuildContainer();
    }

    $timer = round(microtime(TRUE) - $timer);
    if ($timer < 1) {
      sleep(1);
    }
  }
}
