<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Media Migration's field instance settings plugins.
 */
abstract class MediaMigrationFieldInstanceSettingsProcessPluginBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  use MigrationDeriverTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns data of media types which will be available after the migration.
   *
   * @return array
   *   Array of the media type's source data, keyed by the media type ID.
   */
  protected function getPredictedMediaTypeData(): array {
    $media_types = [];
    $media_type_file_plain_source = static::getSourcePlugin('d7_file_plain_type');
    foreach ($media_type_file_plain_source as $row) {
      assert($row instanceof Row);
      $media_types[$row->getSourceProperty('bundle')] = $row->getSource();
    }

    $media_type_file_entity_source = static::getSourcePlugin('d7_file_entity_type');
    try {
      foreach ($media_type_file_entity_source as $row) {
        assert($row instanceof Row);
        $media_types[$row->getSourceProperty('bundle')] = $media_types[$row->getSourceProperty('bundle')] ?? $row->getSource();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // File entity source requirements are not always fulfilled.
    }

    return $media_types;
  }

  /**
   * Returns data about the already existing media types.
   *
   * @return array
   *   Array of some media type data, keyed by the media type ID.
   */
  protected function getExistingMediaTypeData() {
    $existing_media_types = $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple();
    return array_reduce($existing_media_types, function (array $carry, MediaTypeInterface $media_type) {
      if (!$media_type->status()) {
        return $carry;
      }
      $media_type_id = $media_type->id();
      $source_plugin = $media_type->getSource();
      $source_field_definition = $source_plugin->getSourceFieldDefinition($media_type);

      if (!($source_field_definition instanceof FieldDefinitionInterface)) {
        return $carry;
      }

      $source_field_storage_definition = $source_field_definition->getFieldStorageDefinition();
      $carry[$media_type_id] = [
        'bundle' => $media_type_id,
        'source_plugin_id' => $source_plugin->getPluginId(),
        'scheme' => $source_field_storage_definition->getSettings()['uri_scheme'] ?? NULL,
      ];
      return $carry;
    }, []);
  }

}
