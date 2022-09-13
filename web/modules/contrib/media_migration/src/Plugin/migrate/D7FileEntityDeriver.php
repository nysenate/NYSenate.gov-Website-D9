<?php

namespace Drupal\media_migration\Plugin\migrate;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media_migration\FileEntityDealerManagerInterface;
use Drupal\media_migration\FileEntityDealerPluginInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\Plugin\migrate\source\d7\ConfigSourceBase;
use Drupal\migmag\Utility\MigMagArrayUtility;
use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for file entity migrations.
 */
class D7FileEntityDeriver extends D7FileEntityConfigDeriver {

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * The file entity dealer plugin manager.
   *
   * @var \Drupal\media_migration\FileEntityDealerManagerInterface
   */
  protected $fileEntityDealerManager;

  /**
   * D7FileEntityDeriver constructor.
   *
   * @param \Drupal\media_migration\FileEntityDealerManagerInterface $file_entity_dealer_manager
   *   The file entity dealer plugin manager.
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The migration field discovery service.
   */
  public function __construct(FileEntityDealerManagerInterface $file_entity_dealer_manager, FieldDiscoveryInterface $field_discovery) {
    parent::__construct($file_entity_dealer_manager);
    $this->fieldDiscovery = $field_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.file_entity_dealer'),
      $container->get('migrate_drupal.field_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $file_entity_types = static::getSourcePlugin('d7_file_entity_type');

    try {
      $file_entity_types->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      foreach ($file_entity_types as $file_entity_type_row) {
        assert($file_entity_type_row instanceof Row);
        [
          'types' => $types,
          'schemes' => $schemes,
        ] = $file_entity_type_row->getSource();

        foreach (explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $types) as $type) {
          foreach (explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $schemes) as $scheme) {
            $dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme);

            // No plugin was found for this file entity type row.
            if (!($dealer_plugin instanceof FileEntityDealerPluginInterface)) {
              throw new \LogicException(sprintf('No FileEntityDealer plugin applies for file entities with type "%s" and with scheme "%s/*"', $type, $scheme));
            }

            $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
            $derivative_definition = $base_plugin_definition + [
              'migration_dependencies' => ['required' => []],
            ];
            $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
              $type,
              $scheme,
            ]);
            // Create the migration derivative.
            $derivative_definition['migration_tags'][] = MediaMigration::MIGRATION_TAG_MAIN;
            $derivative_definition['migration_tags'][] = MediaMigration::MIGRATION_TAG_CONTENT;
            $derivative_definition['source']['type'] = $type;
            $derivative_definition['source']['scheme'] = $scheme;
            $derivative_definition['source']['types'] = $types;
            $derivative_definition['source']['schemes'] = $schemes;
            $derivative_definition['source']['destination_media_type_id'] = $destination_media_type_id;
            $derivative_definition['source']['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
            $derivative_definition['label'] = $this->t('@label (@type)', [
              '@label' => $base_plugin_definition['label'],
              '@type' => $dealer_plugin->getDestinationMediaTypeLabel(),
            ]);

            // Add source field migration ID to source.
            if (array_search('d7_file_entity_source_field_config', $derivative_definition['migration_dependencies']['required'], TRUE) !== FALSE) {
              $derivative_definition['source']['source_field_migration_id'] = 'd7_file_entity_source_field_config' . PluginBase::DERIVATIVE_SEPARATOR . $destination_media_type_id;
            }

            // Post-process migration dependencies: instead of depending on
            // migrations based on their base plugin ID, it is better to use the
            // corresponding derivatives where possible.
            $derived_migration_ids = [
              'd7_file_entity_type',
              'd7_file_entity_source_field',
              'd7_file_entity_source_field_config',
              'd7_file_entity_widget',
              'd7_file_entity_formatter',
            ];
            $suffix = PluginBase::DERIVATIVE_SEPARATOR . $destination_media_type_id;
            MigMagArrayUtility::addSuffixToArrayValues(
              $derivative_definition['migration_dependencies']['required'],
              $derived_migration_ids,
              $suffix
            );

            // Update migration lookups.
            $derived_migration_id_replacements = array_reduce(
              $derived_migration_ids,
              function (array $carry, string $base_id) use ($suffix) {
                $carry[$base_id] = $base_id . $suffix;
                return $carry;
              },
              []
            );
            MigMagMigrationUtility::updateMigrationLookups(
              $derivative_definition,
              $derived_migration_id_replacements
            );

            // Add bundle field processes.
            $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($derivative_definition);
            $process_keys_before = array_keys($derivative_definition['process']);
            assert($migration instanceof Migration);
            $this->fieldDiscovery->addBundleFieldProcesses($migration, 'file', $type);
            $derivative_definition = $migration->getPluginDefinition();
            if (!empty(array_diff(array_keys($derivative_definition['process']), $process_keys_before))) {
              $derivative_definition['migration_dependencies']['required'][] = 'd7_field_instance';
            }

            // Add the corresponding file migration as migration dependency.
            switch ($scheme) {
              case 'public':
                $derivative_definition['migration_dependencies']['required'][] = 'd7_file';
                break;

              case 'private':
                $derivative_definition['migration_dependencies']['required'][] = 'd7_file_private';
                break;
            }

            $dealer_plugin->alterMediaEntityMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());

            $this->derivatives[$derivative_id] = $derivative_definition;
            $this->derivatives[$derivative_id]['source']['media_migration_original_id'] = $base_plugin_definition['id'] . PluginBase::DERIVATIVE_SEPARATOR . $derivative_id;
          }
        }
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
