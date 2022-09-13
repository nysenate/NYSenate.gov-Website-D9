<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Variable;
use Drupal\media_migration\Utility\MigrationPluginTool;
use Drupal\media_migration\Utility\SourceDatabase;
use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migmag\Utility\MigMagSourceUtility;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for media_wysiwyg plugins.
 */
abstract class MediaWysiwygPluginBase extends PluginBase implements MediaWysiwygInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $source_entity_type_id = $configuration['source_entity_type_id'] ?? NULL;
    if (!$source_entity_type_id && count($plugin_definition['entity_type_map']) === 1) {
      $source_entity_type_id_keys = array_keys($plugin_definition['entity_type_map']);
      $configuration['source_entity_type_id'] = reset($source_entity_type_id_keys);
    }

    if (
      empty($configuration['source_entity_type_id']) ||
      empty($plugin_definition['entity_type_map'][$configuration['source_entity_type_id']])
    ) {
      throw new PluginException(
        sprintf(
          "The MediaWysiwyg plugin instance of class '%s' cannot be instantiated with the following configuration: %s",
          get_class($this),
          Variable::export($configuration)
        )
      );
    }

    $configuration += [
      'destination_entity_type_id' => $plugin_definition['entity_type_map'][$configuration['source_entity_type_id']],
    ];

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $migrations, Row $row) {
    $matching_migration_plugin_ids = MigrationPluginTool::getContentEntityMigrations($migrations, $this->configuration['destination_entity_type_id']);

    foreach (array_keys($matching_migration_plugin_ids) as $migration_plugin_id) {
      $migrations = $this->appendProcessor($migrations, $migration_plugin_id, $row->getSourceProperty('field_name'), $this->configuration['source_entity_type_id']);
    }

    return $migrations;
  }

  /**
   * Appends the media wysiwyg migrate processor to a field.
   *
   * @param array $migrations
   *   The array of migrations.
   * @param string $migration_id
   *   The migration to adjust.
   * @param string $field_name_in_source
   *   The migration field name.
   * @param string $source_entity_type
   *   The source entity type.
   *
   * @return array
   *   The updated array of migrations.
   */
  protected function appendProcessor(array $migrations, string $migration_id, string $field_name_in_source, string $source_entity_type) {
    $extra_processes = [
      [
        'plugin' => 'media_wysiwyg_filter',
      ],
    ];

    // Add 'img_tag_to_embed' and 'ckeditor_link_file_to_linkit' text process
    // plugins and their dependencies when they're required.
    if (
      isset($migrations['d7_filter_format']) &&
      ($filter_format_source = MigMagSourceUtility::getSourcePlugin('d7_filter_format')) instanceof DrupalSqlBase
    ) {
      $source_connection = $filter_format_source->getDatabase();
      $file_link_deps_added = FALSE;

      if (!empty(SourceDatabase::getFormatsHavingFileLink($source_connection, $field_name_in_source, $source_entity_type))) {
        $extra_processes[] = ['plugin' => 'ckeditor_link_file_to_linkit'];

        $migrations[$migration_id]['migration_dependencies']['required'] = array_unique(
          array_merge(
            array_values($migrations[$migration_id]['migration_dependencies']['required'] ?? []),
            [
              'd7_file_plain',
              'd7_file_entity',
            ]
          )
        );
        $file_link_deps_added = TRUE;
      }

      if (!empty(SourceDatabase::getFormatsUsingTag($source_connection, 'img', $field_name_in_source, $source_entity_type))) {
        $extra_processes[] = ['plugin' => 'img_tag_to_embed'];

        if (!$file_link_deps_added) {
          $migrations[$migration_id]['migration_dependencies']['required'] = array_unique(
            array_merge(
              array_values($migrations[$migration_id]['migration_dependencies']['required'] ?? []),
              [
                'd7_file_plain:image:public',
                'd7_file_entity:image:public',
              ]
            )
          );
        }
      }
    }

    $migration_processes = $migrations[$migration_id]['process'] ?? [];
    $processes_needs_extra_processor = [];

    // The field might be renamed or completely removed by others: Media
    // Migration should check the processes' source values.
    // @todo Check subprocesses and find a way to handle array sources.
    foreach ($migration_processes as $process_key => $original_process) {
      $associative_process = MigMagMigrationUtility::getAssociativeMigrationProcess($original_process);

      foreach ($associative_process as $process_plugin_key => $process_plugin_config) {
        if (isset($process_plugin_config['source']) && $process_plugin_config['source'] === $field_name_in_source) {
          $processes_needs_extra_processor[$process_key][] = $process_plugin_key;
          $migration_processes[$process_key] = $associative_process;
        }
      }
    }

    // Add the text field value processes to the corresponding destination
    // properties.
    foreach ($processes_needs_extra_processor as $process_key => $process_plugin_keys) {
      foreach ($process_plugin_keys as $process_plugin_key) {
        // The process should be added right after the collected key (since the
        // field value array might be converted to a string value what the
        // process plugin does not handle). Since the process pipeline not
        // always have auto-incremented integer keys, Media Migration has to
        // work with the "real" key positions.
        $real_process_key_position = array_search($process_plugin_key, array_keys($migration_processes[$process_key])) + 1;
        $leading_processes = array_slice($migration_processes[$process_key], 0, $real_process_key_position);
        $trailing_processes = array_slice($migration_processes[$process_key], $real_process_key_position);
        $migrations[$migration_id]['process'][$process_key] = array_merge(
          $leading_processes,
          $extra_processes,
          $trailing_processes
        );
      }
    }

    return $migrations;
  }

}
