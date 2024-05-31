<?php

namespace Drupal\media_migration\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_migration\FileEntityDealerManagerInterface;
use Drupal\media_migration\FileEntityDealerPluginInterface;
use Drupal\media_migration\MediaMigration;
use Drupal\media_migration\Plugin\migrate\source\d7\ConfigSourceBase;
use Drupal\migmag\Utility\MigMagArrayUtility;
use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for file entities' configuration entity migrations.
 */
class D7FileEntityConfigDeriver extends DeriverBase implements ContainerDeriverInterface {

  use MigrationDeriverTrait;
  use StringTranslationTrait;

  /**
   * The file entity dealer plugin manager.
   *
   * @var \Drupal\media_migration\FileEntityDealerManagerInterface
   */
  protected $fileEntityDealerManager;

  /**
   * D7FileEntityConfigDeriver constructor.
   *
   * @param \Drupal\media_migration\FileEntityDealerManagerInterface $file_entity_dealer_manager
   *   The file entity dealer plugin manager.
   */
  public function __construct(FileEntityDealerManagerInterface $file_entity_dealer_manager) {
    $this->fileEntityDealerManager = $file_entity_dealer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.file_entity_dealer')
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

    assert($file_entity_types instanceof DrupalSqlBase);

    try {
      foreach ($file_entity_types as $file_entity_type_row) {
        assert($file_entity_type_row instanceof Row);
        [
          'types' => $types,
          'schemes' => $schemes,
        ] = $file_entity_type_row->getSource();

        // Multiple types and schemes may have the same destination media type
        // ID.
        $type = explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $types)[0];
        $scheme = explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $schemes)[0];
        $dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme);

        // No plugin was found for this file entity type row.
        if (!($dealer_plugin instanceof FileEntityDealerPluginInterface)) {
          throw new \LogicException(sprintf('No FileEntityDealer plugin applies for file entities with type "%s" and with scheme "%s/*"', $type, $scheme));
        }

        $destination_media_type_id = $dealer_plugin->getDestinationMediaTypeId();
        $source_plugin_id = $base_plugin_definition['source']['plugin'];
        $derivative_definition = $base_plugin_definition + [
          'migration_dependencies' => ['required' => []],
        ];
        // Create the migration derivative.
        $derivative_definition['migration_tags'][] = MediaMigration::MIGRATION_TAG_MAIN;
        $derivative_definition['migration_tags'][] = MediaMigration::MIGRATION_TAG_CONFIG;
        $derivative_definition['source']['schemes'] = $schemes;
        $derivative_definition['source']['types'] = $types;
        $derivative_definition['source']['destination_media_type_id'] = $destination_media_type_id;
        $derivative_definition['source']['source_field_name'] = $dealer_plugin->getDestinationMediaSourceFieldName();
        $derivative_definition['label'] = $this->t('@label (@type)', [
          '@label' => $base_plugin_definition['label'],
          '@type' => $dealer_plugin->getDestinationMediaTypeLabel(),
        ]);

        // Post-process migration dependencies: instead of depending on
        // migrations based on their base plugin ID, it is better to use the
        // corresponding derivatives where possible.
        $derived_migration_ids = [
          'd7_file_plain_type',
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

        switch ($source_plugin_id) {
          case 'd7_file_entity_type':
            $dealer_plugin->alterMediaTypeMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());
            break;

          case 'd7_file_entity_source_field_storage':
            $dealer_plugin->alterMediaSourceFieldStorageMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());
            break;

          case 'd7_file_entity_source_field_instance':
            $dealer_plugin->alterMediaSourceFieldInstanceMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());
            break;

          case 'd7_file_entity_field_widget':
            $dealer_plugin->alterMediaSourceFieldWidgetMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());
            break;

          case 'd7_file_entity_field_formatter':
            $dealer_plugin->alterMediaFieldFormatterMigrationDefinition($derivative_definition, $file_entity_types->getDatabase());
            break;
        }

        $this->derivatives[$destination_media_type_id] = $derivative_definition;
        $this->derivatives[$destination_media_type_id]['source']['media_migration_original_id'] = $base_plugin_definition['id'] . PluginBase::DERIVATIVE_SEPARATOR . $destination_media_type_id;
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
