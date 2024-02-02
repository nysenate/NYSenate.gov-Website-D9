<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\Plugin\migrate\source\d7\Field;
use Drupal\field\Plugin\migrate\source\d7\FieldInstance;
use Drupal\field\Plugin\migrate\source\d7\ViewMode;
use Drupal\media_migration\Plugin\MediaWysiwyg\Paragraphs;
use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migmag\Utility\MigMagSourceUtility;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;
use Psr\Log\LoggerInterface;

/**
 * Service for performing migration plugin alterations.
 */
class MigratePluginAlterer {

  /**
   * The plugin.manager.media_wysiwyg service.
   *
   * @var \Drupal\media_migration\MediaWysiwygPluginManager
   */
  protected $pluginManagerMediaWysiwyg;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a MigratePluginAlterer object.
   *
   * @param \Drupal\media_migration\MediaWysiwygPluginManager $plugin_manager_media_wysiwyg
   *   The Media WYSIWYG plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(MediaWysiwygPluginManager $plugin_manager_media_wysiwyg, LoggerInterface $logger, ModuleHandlerInterface $module_handler) {
    $this->pluginManagerMediaWysiwyg = $plugin_manager_media_wysiwyg;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Alters migrate plugins.
   *
   * @param array $migrations
   *   The array of migration plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If a plugin cannot be found.
   */
  public function alter(array &$migrations) {
    $this->alterFieldMigrations($migrations);
    $this->addMediaWysiwygProcessor($migrations);
    $this->alterFilterFormatMigration($migrations);
  }

  /**
   * Alters field migrations from file_entity/media in 7 to media in 8.
   *
   * @param array $migrations
   *   The array of migration plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If a plugin cannot be found.
   */
  protected function alterFieldMigrations(array &$migrations) {
    foreach ($migrations as &$migration) {
      // If this is not a Drupal 7 migration, we can skip processing it.
      if (!in_array('Drupal 7', $migration['migration_tags'] ?? [])) {
        continue;
      }
      $source = NULL;
      if (!empty($migration['source']['plugin'])) {
        $source = MigMagSourceUtility::getSourcePlugin($migration['source']);
        if (is_a($migration['class'], FieldMigration::class, TRUE)) {

          // Field storage, instance, widget and formatter migrations.
          if (is_a($source, Field::class) || is_a($source, FieldInstance::class)) {
            static::mapMigrationProcessValueToMedia($migration, 'entity_type');
          }
        }

        // View Modes.
        if (is_a($source, ViewMode::class)) {
          static::mapMigrationProcessValueToMedia($migration, 'targetEntityType');
        }

        // D7 field instance migrations should have optional dependency on
        // media types — just like it already does for node type, comment type
        // and vocabulary type in core.
        if ($migration['source']['plugin'] === 'd7_field_instance') {
          $migration['migration_dependencies']['optional'][] = 'd7_file_entity_type';
        }
      }
    }
  }

  /**
   * Appends text processors to transform D7 tokens to embeds.
   *
   * Find field instances with text processing and pass them to a
   * MediaWysiwyg plugin that will add processors to the respective
   * migrations.
   *
   * @param array $migrations
   *   The array of migration plugins.
   *
   * @see \Drupal\media_migration\Plugin\MediaWysiwyg\Node
   */
  protected function addMediaWysiwygProcessor(array &$migrations) :void {
    $field_instance_migrations = array_filter($migrations, function (array $definition) {
      return $definition['id'] === 'd7_field_instance';
    });

    if (empty($field_instance_migrations)) {
      return;
    }

    $fields_in_content_entity_migrations_processed = [];
    $source_entity_types_without_plugin = [];

    foreach ($field_instance_migrations as $field_instance_migration_definition) {
      $field_instance_source = MigMagSourceUtility::getSourcePlugin($field_instance_migration_definition['source']);

      if ($field_instance_source instanceof RequirementsInterface) {
        try {
          $field_instance_source->checkRequirements();
        }
        catch (RequirementsException $e) {
          continue;
        }
      }

      foreach ($field_instance_source as $row) {
        assert($row instanceof Row);
        $source_entity_type_id = $row->getSourceProperty('entity_type');

        if (
          !in_array($source_entity_type_id, $source_entity_types_without_plugin, TRUE) &&
          $row->getSourceProperty('field_definition')['module'] === 'text' &&
          $row->getSourceProperty('settings/text_processing') !== 0
        ) {
          $field_name = $row->getSourceProperty('field_name');

          if (
            !empty($fields_in_content_entity_migrations_processed[$source_entity_type_id]) &&
            in_array($field_name, $fields_in_content_entity_migrations_processed[$source_entity_type_id], TRUE)
          ) {
            continue;
          }

          try {
            $plugin = $this->pluginManagerMediaWysiwyg->createInstanceFromSourceEntityType($source_entity_type_id);
            $migrations = $plugin->process($migrations, $row);
          }
          catch (PluginException $e) {
            $source_entity_types_without_plugin[] = $source_entity_type_id;
            $this->logger->warning(
              sprintf(
                "Could not find a MediaWysiwyg plugin for field '%s' used in source entity type '%s'. You probably need to create a new one. Have a look at '%s' for an example.",
                $row->getSourceProperty('field_name'),
                $source_entity_type_id,
                Paragraphs::class
              )
            );
          }

          $fields_in_content_entity_migrations_processed[$source_entity_type_id][] = $field_name;
        }
      }
    }
  }

  /**
   * Maps Drupal 7 media_filter filter plugin to a Drupal 8|9 filter plugin.
   *
   * If Entity Embed module is installed on the destination site, this method
   * maps the media_embed filter plugin (Drupal 7 Media WYSIWYG module) to
   * entity_embed filter plugin (from the Entity Embed module).
   * If Entity Embed is unavailable, the media_filter filter will be mapped to
   * media_embed filter (from core Media Library module).
   *
   * @param array $migrations
   *   The array of migration plugins.
   */
  protected function alterFilterFormatMigration(array &$migrations) :void {
    $destination_filter_plugin_id = MediaMigration::getEmbedTokenDestinationFilterPlugin();
    // If entity_embed is not installed, the destination entity type of the
    // "d7_embed_button_media" migration is missing.
    if (!$this->moduleHandler->moduleExists('entity_embed')) {
      unset($migrations['d7_embed_button_media']);
    }

    if (isset($migrations['d7_filter_format']) && MediaMigration::embedTokenDestinationFilterPluginIsValid($destination_filter_plugin_id)) {
      $migrations['d7_filter_format']['process']['filters']['process']['id']['map']['media_filter'] = $destination_filter_plugin_id;
    }
    else {
      // We don't know the transform type or the filter format migration does
      // not exist.
      return;
    }

    if (MediaMigration::MEDIA_TOKEN_DESTINATION_FILTER_ENTITY_EMBED == $destination_filter_plugin_id && isset($migrations['d7_filter_format']) && isset($migrations['d7_embed_button_media'])) {
      $migrations['d7_filter_format']['migration_dependencies']['required'][] = 'd7_embed_button_media';
    }

    // We have to add <drupal-entity> or <drupal-media> to the allowed html
    // tag's list.
    if (isset($migrations['d7_filter_format'])) {
      $filter_plugin_settings_processes = MigMagMigrationUtility::getAssociativeMigrationProcess($migrations['d7_filter_format']['process']['filters']['process']['settings']);
      $filter_plugin_settings_processes[] = [
        'plugin' => 'filter_settings_embed_media',
      ];
      $migrations['d7_filter_format']['process']['filters']['process']['settings'] = $filter_plugin_settings_processes;
    }
  }

  /**
   * Maps a migration's property from "file" to "media".
   *
   * @param array $migration
   *   The migration to alter.
   * @param string $property
   *   The property to change.
   */
  public static function mapMigrationProcessValueToMedia(array &$migration, string $property) {
    if (!empty($migration['source'][__FUNCTION__])) {
      return;
    }

    try {
      $value = static::getSourceValueOfMigrationProcess($migration, $property);
      switch ($value) {
        case 'file':
          $migration['source']["media_migration_$property"] = 'media';
          $migration['process'][$property] = "media_migration_$property";
          break;

        case NULL:
          // The value of the property cannot be determined, it might be a
          // dynamic value.
          $entity_type_process = MigMagMigrationUtility::getAssociativeMigrationProcess($migration['process'][$property]);
          $entity_type_process[] = [
            'plugin' => 'static_map',
            'map' => [
              'file' => 'media',
            ],
            'bypass' => TRUE,
          ];
          $migration['process'][$property] = $entity_type_process;
          break;
      }
    }
    catch (\LogicException $e) {
      // The process property does not exists, nothing to do.
    }

    $migration['source'][__FUNCTION__] = TRUE;
  }

  /**
   * Gets the value of a process property if it is not dynamically calculated.
   *
   * @param array $migration
   *   The migration plugin's definition array.
   * @param string $process_property_key
   *   The property to check.
   *
   * @return mixed|null
   *   The value of the property if it can be determined, or NULL if it seems
   *   to be dynamic.
   *
   * @throws \LogicException.
   *   When the process property does not exists.
   */
  public static function getSourceValueOfMigrationProcess(array $migration, string $process_property_key) {
    if (
      !array_key_exists('process', $migration) ||
      !is_array($migration['process']) ||
      !array_key_exists($process_property_key, $migration['process'])
    ) {
      throw new \LogicException('No corresponding process found');
    }

    $property_processes = MigMagMigrationUtility::getAssociativeMigrationProcess($migration['process'][$process_property_key]);
    $the_first_process = reset($property_processes);
    $property_value = NULL;

    if (
      !array_key_exists('source', $migration) ||
      count($property_processes) !== 1 ||
      $the_first_process['plugin'] !== 'get' ||
      empty($the_first_process['source'])
    ) {
      return NULL;
    }

    $process_value_source = $the_first_process['source'];

    // Parsing string values like "whatever" or "constants/whatever/key".
    // If the property is set to an already available value (e.g. a constant),
    // we don't need our special mapping applied.
    $property_value = NestedArray::getValue($migration['source'], explode(Row::PROPERTY_SEPARATOR, $process_value_source), $key_exists);

    // Migrations using the "embedded_data" source plugin actually contain
    // rows with source values.
    if (!$key_exists && $migration['source']['plugin'] === 'embedded_data') {
      $embedded_rows = $migration['source']['data_rows'] ?? [];
      $embedded_property_values = array_reduce($embedded_rows, function (array $carry, array $row) use ($process_value_source) {
        $embedded_value = NestedArray::getValue($row, explode(Row::PROPERTY_SEPARATOR, $process_value_source));
        $carry = array_unique(array_merge($carry, [$embedded_value]));
        return $carry;
      }, []);
      return count($embedded_property_values) === 1
        ? $embedded_property_values[0]
        : NULL;
    }

    return $key_exists ? $property_value : NULL;
  }

}
