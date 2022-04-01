<?php

/**
 * @file
 * Drush (< 9) integration.
 */

use Drupal\simple_sitemap\Queue\QueueWorker;

/**
 * Implements hook_drush_command().
 */
function simple_sitemap_drush_command() {
  $items['simple-sitemap-generate'] = [
    'description' => 'Regenerate all XML sitemap variants or continue generation.',
    'callback' => 'drush_simple_sitemap_generate',
    'drupal dependencies' => ['simple_sitemap'],
    'aliases' => ['ssg'],
  ];

  $items['simple-sitemap-rebuild-queue'] = [
    'description' => 'Queue all sitemap variants for regeneration.',
    'callback' => 'drush_simple_sitemap_rebuild_queue',
    'drupal dependencies' => ['simple_sitemap'],
    'aliases' => ['ssr'],
  ];

  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate the XML sitemaps according to the module settings.
 */
function drush_simple_sitemap_generate() {
  \Drupal::service('simple_sitemap.generator')->generateSitemap(QueueWorker::GENERATE_TYPE_DRUSH);
}

/**
 * Callback function for hook_drush_command().
 *
 * Rebuild the sitemap queue for all sitemap variants.
 */
function drush_simple_sitemap_rebuild_queue() {
  \Drupal::service('simple_sitemap.generator')->rebuildQueue();
}
