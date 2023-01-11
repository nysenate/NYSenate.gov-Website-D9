<?php

namespace Drupal\location_migration\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for Location migration deriver classes.
 */
abstract class LocationDeriverBase extends DeriverBase {

  use MigrationDeriverTrait;
  use StringTranslationTrait;

  /**
   * Returns source plugin configurations for migration plugin derivatives.
   *
   * @param array $base_plugin_definition
   *   A base migration plugin definition.
   *
   * @return array
   *   The source plugin definition for the given migration definition's
   *   derivatives, keyed by the derivative ID.
   */
  public static function getDerivatives(array $base_plugin_definition): array {
    $source = static::getSourcePlugin($base_plugin_definition['source']['plugin']);
    assert($source instanceof DrupalSqlBase);

    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the source plugin requirements failed, that means we do not have a
      // Drupal source database configured - there is nothing to generate.
      return [];
    }

    $derivatives = [];
    try {
      foreach ($source as $row) {
        assert($row instanceof Row);
        $entity_type = $row->getSourceProperty('entity_type');
        $bundle = $row->getSourceProperty('bundle');
        $values = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
        ];
        $derivative_id = $bundle !== NULL
          ? $entity_type . PluginBase::DERIVATIVE_SEPARATOR . $bundle
          : $entity_type;
        $derivatives[$derivative_id] = $values;
      }
    }
    catch (\Exception $e) {
    }

    return $derivatives;
  }

  /**
   * Constructs a derivative label.
   *
   * @param array $migration_plugin_definition
   *   The (final) migration plugin definition of a derivative.
   */
  public function applyDerivativeLabel(array &$migration_plugin_definition): void {
    $entity_type_id = $migration_plugin_definition['source']['entity_type'];
    $bundle = $migration_plugin_definition['source']['bundle'] ?? NULL;

    $migration_plugin_definition['label'] = !empty($bundle)
      ? $this->t('@label (@entity-type, @bundle)', [
        '@label' => $migration_plugin_definition['label'],
        '@entity-type' => $entity_type_id,
        '@bundle' => $bundle,
      ])
      : $this->t('@label (@entity-type)', [
        '@label' => $migration_plugin_definition['label'],
        '@entity-type' => $entity_type_id,
      ]);
  }

}
