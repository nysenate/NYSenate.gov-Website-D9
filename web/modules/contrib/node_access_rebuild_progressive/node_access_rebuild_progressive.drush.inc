<?php

/**
 * @file
 * Defines a node access rebuild command.
 */

use Consolidation\AnnotatedCommand\CommandResult;

/**
 * Implements hook_drush_command().
 *
 * {@inheritdoc}
 */
function node_access_rebuild_progressive_drush_command() {

  $items['node-access-rebuild-progressive'] = [
    'description' => "Fully rebuild node access.",
    'callback' => 'drush_node_access_rebuild_progressive_rebuild',
  ];
  return $items;
}

/**
 * Rebuilds the node access grants table.
 */
function drush_node_access_rebuild_progressive_rebuild(): CommandResult {
  return _drush_node_access_rebuild_progressive_rebuild();
}
