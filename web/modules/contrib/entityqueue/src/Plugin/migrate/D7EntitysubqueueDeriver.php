<?php

namespace Drupal\entityqueue\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;

/**
 * Deriver for Drupal 7 entity subqueues.
 */
class D7EntitysubqueueDeriver extends DeriverBase {
  use MigrationDeriverTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entityqueues = static::getSourcePlugin('d7_entityqueue');
    try {
      $entityqueues->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      /** @var \Drupal\migrate\Row $row */
      foreach ($entityqueues as $row) {
        $values = $base_plugin_definition;
        foreach ($row->getSourceProperty('settings')['target_bundles'] as $target_bundle) {
          $values['migration_dependencies']['required'][] = 'd7_' . $row->getSourceProperty('target_type') . ':' . $target_bundle;
        }

        /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);
        $this->derivatives[$row->getSourceProperty('name')] = $migration->getPluginDefinition();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
    }
    return $this->derivatives;
  }

}
