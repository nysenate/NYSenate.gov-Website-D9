<?php

/**
 * @file
 * File for Drush Integration.
 */
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\config_update\ConfigListInterface;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\Core\Config\StorageInterface;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_help_alter().
 */
function migrate_devel_drush_help_alter(&$command) {
  if ($command['command'] === 'migrate-status') {
    $command['options']['migrate-debug'] = 'Enable Debug Mode';
  }
}

/**
 * Implements hook_drush_command_alter().
 */
function migrate_devel_drush_command_alter(&$command) {
  $cmd = $command['command'];
  if ($cmd === 'migrate-import' || $cmd === 'migrate-status') {
    // Reset all migrations
    if (drush_get_option('migrate-debug')) {
      migrate_devel_rebuild_migrations();
    }
  }
}

/**
 * Clears cache for migrations and reverts config of migrations if needed.
 */
function migrate_devel_rebuild_migrations() {
  // @TODO: Remove this legacy support of migrate_plus.
  // After #2752335, it uses the default Migrate Plugin Manager
  /* @var $discovery CachedDiscoveryInterface */
  if (\Drupal::hasService('plugin.manager.config_entity_migration')) {
    $discovery = \Drupal::service('plugin.manager.config_entity_migration');
  } else {
    $discovery = \Drupal::service('plugin.manager.migration');
  }

  // Reset cached migrations
  $discovery->clearCachedDefinitions();

  /* @var $discovery CachedDiscoveryInterface */
  if (\Drupal::moduleHandler()->moduleExists('migrate_plus')) {
    migrate_devel_revert_migrate_config($discovery);
  }

  \Drupal::logger(dt('Reset Cached Migrations'), LogLevel::DEBUG);
}

/**
 * Reverts migrate config for migrate_plus
 *
 * @param CachedDiscoveryInterface $discovery
 */
function migrate_devel_revert_migrate_config($discovery) {
  // If migrate_plus and config_update exists, revert the config.
  if (\Drupal::moduleHandler()->moduleExists('config_update')) {
    /* @var $config_revert ConfigRevertInterface */
    $config_revert = \Drupal::service('config_update.config_update');

    foreach ($discovery->getDefinitions() as $definition) {
      $config_revert->revert('migration', $definition['id']);
    }

    \Drupal::logger(dt('Reverted Existing Migration Configs'));

    // Revert missing config
    migrate_devel_revert_missing_config('migration');
  } else {
    \Drupal::logger(dt('Missing config_update for revert.'));
  }
}

/**
 * Reverts missing config for a specific config type.
 *
 * @param string $type
 */
function migrate_devel_revert_missing_config($type) {
  /* @var ConfigRevertInterface $config_revert */
  $config_revert = \Drupal::service('config_update.config_update');

  // Now we need to add any new migrations.
  /* @var ConfigListInterface $config_lister */
  $config_lister = \Drupal::service('config_update.config_list');

  // Add any new migrations we need to.
  list($active_list, $install_list, $optional_list) = $config_lister->listConfig('type', $type);
  $missing = array_diff($install_list, $active_list);

  /* @var StorageInterface $config_reader */
  $config_reader = \Drupal::service('config_update.extension_storage');

  foreach ($missing as $name) {
    $config = $config_reader->read($name);
    $config_type = $config_lister->getTypeNameByConfigName($name);
    $definition = $config_lister->getType($config_type);
    $id_key = $definition->getKey('id');

    if ($config_revert->import($config_type, $config[$id_key])) {
      \Drupal::logger(dt('Imported @config', ['@config' => $name]), LogLevel::ALERT);
    }
  }
}

