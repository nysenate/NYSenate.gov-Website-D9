<?php

namespace Drupal\media_migration;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media_migration\Plugin\migrate\source\d7\ConfigSourceBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation of media dealer plugins.
 */
abstract class MediaDealerBase extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * The media source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $mediaSourceManager;

  /**
   * The field type plugin manager service.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

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
   * @param \Drupal\Component\Plugin\PluginManagerInterface $media_source_manager
   *   Media source plugin manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $media_source_manager, FieldTypePluginManagerInterface $field_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaSourceManager = $media_source_manager;
    $this->fieldTypeManager = $field_type_manager;
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
      $container->get('plugin.manager.media.source'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeIdBase() {
    return $this->pluginDefinition['destination_media_type_id_base'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeId() {
    return implode('_', array_filter([
      $this->getDestinationMediaTypeIdBase(),
      $this->configuration['scheme'] === 'public' ? NULL : $this->configuration['scheme'],
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeLabel() {
    return implode(' ', array_filter([
      $this->getDestinationMediaTypeSourceFieldLabel(),
      $this->configuration['scheme'] === 'public' ? NULL : "({$this->configuration['scheme']})",
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    return ucfirst(preg_replace('/[\W|_]+/', ' ', strtolower($this->getDestinationMediaTypeIdBase())));
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return implode('_', array_filter([
      'field',
      'media',
      str_replace(':', '_', $this->getDestinationMediaSourcePluginId()),
      $this->configuration['scheme'] === 'public' ? NULL : $this->configuration['scheme'],
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourcePluginId() {
    return $this->pluginDefinition['destination_media_source_plugin_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterMediaTypeMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function alterMediaSourceFieldStorageMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function alterMediaSourceFieldInstanceMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function alterMediaSourceFieldWidgetMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function alterMediaFieldFormatterMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldStorageRow(Row $row, Connection $connection): void {
    $dummy_field_storage = $this->getMediaSourceFieldStorage();
    $additional_properties = [
      'field_type' => $dummy_field_storage->getType(),
      'settings' => $dummy_field_storage->getSettings(),
    ];
    $additional_properties['settings']['uri_scheme'] = $this->configuration['scheme'];

    foreach ($additional_properties as $source_property => $source_value) {
      $row->setSourceProperty($source_property, $source_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldInstanceRow(Row $row, Connection $connection): void {
    $field_instance_default = $this->getMediaSourceFieldInstance();
    $settings = $field_instance_default->getSettings();
    $default_extensions = $settings['file_extensions'] ?? '';
    $discovered_extensions = $row->getSourceProperty('file_extensions') ?? '';
    $merged_file_extensions = implode(' ', array_filter(array_unique(array_merge(explode(' ', $default_extensions), explode(ConfigSourceBase::MULTIPLE_SEPARATOR, $discovered_extensions)))));
    $settings['file_extensions'] = $merged_file_extensions;
    $settings['uri_scheme'] = $this->configuration['scheme'];
    $row->setSourceProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldWidgetRow(Row $row, Connection $connection): void {
    $source_field_definition = $this->fieldTypeManager->getDefinition($this->getMediaSourceFieldStorage()->getType(), FALSE) ?? [];
    $default_widget = $source_field_definition['default_widget'] ?? NULL;

    if ($default_widget) {
      $row->setSourceProperty('options', [
        'type' => $default_widget,
        'weight' => 0,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {
    $source_field_definition = $this->fieldTypeManager->getDefinition($this->getMediaSourceFieldStorage()->getType(), FALSE) ?? [];
    $default_formatter = $source_field_definition['default_formatter'] ?? NULL;

    if ($default_formatter) {
      $row->setSourceProperty('options', [
        'type' => $default_formatter,
        'weight' => 0,
        'label' => 'visually_hidden',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareMediaTypeRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void {}

  /**
   * Returns a media source field instance.
   *
   * The returned field instance ("field_config") entity that matches the media
   * source plugin ID. When the destination media type does not exist, this is a
   * new, unsaved media source field instance.
   *
   * The returned entity can be used for pre-populating the media type's source
   * field's instance settings, e.g. for keeping every, previously allowed file
   * extensions.
   *
   * @return \Drupal\field\FieldConfigInterface|null
   *   A matching field instance config for the destination media type, or NULL
   *   if it cannot be instantiated.
   */
  protected function getMediaSourceFieldInstance() {
    $preexisting_field_instance = $this->entityTypeManager->getStorage('field_config')->load(implode('.', [
      'media',
      $this->getDestinationMediaTypeId(),
      $this->getDestinationMediaSourceFieldName(),
    ]));
    if ($preexisting_field_instance) {
      assert($preexisting_field_instance instanceof FieldConfigInterface);
      return $preexisting_field_instance;
    }

    if (!($storage = $this->getMediaSourceFieldStorage())) {
      return NULL;
    }
    $field_config = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => $this->getDestinationMediaTypeId(),
      'label' => $this->getDestinationMediaTypeLabel(),
      'required' => TRUE,
    ]);

    if (!($field_config instanceof FieldConfigInterface)) {
      return NULL;
    }

    $default_settings = $this->fieldTypeManager->getDefaultFieldSettings($field_config->getType());
    $extensions = explode(' ', $default_settings['file_extensions'] ?? '');
    switch ($this->getDestinationMediaSourcePluginId()) {
      case 'audio_file':
        // Using the same defaults what the AudioFile source plugin defines.
        // @see \Drupal\media\Plugin\media\Source\AudioFile::createSourceField()
        $extensions = ['mp3', 'wav', 'aac'];
        break;

      case 'image':
        // Using the same defaults what the Image source plugin defines.
        // @see \Drupal\media\Plugin\media\Source\Image::createSourceField()
        break;

      case 'video_file':
        // Using the same defaults what the VideoFile source plugin defines.
        // @see \Drupal\media\Plugin\media\Source\VideoFile::createSourceField()
        $extensions = ['mp4'];
        break;

      case 'file':
        // Using the same defaults what the File source plugin defines.
        // @see \Drupal\media\Plugin\media\Source\File::createSourceField()
        $extensions = ['txt', 'doc', 'docx', 'pdf'];
        break;
    }

    $default_settings['file_extensions'] = implode(' ', $extensions);
    $field_config->set('settings', $default_settings);

    return $field_config;
  }

  /**
   * Returns a media source field storage.
   *
   * The returned field storage ("field_storage_config") entity that matches the
   * media source plugin ID. When the destination media type does not exist,
   * this is a new, unsaved media source field storage entity.
   *
   * The returned entity can be used for pre-populating the media type's source
   * field's storage settings.
   *
   * @return \Drupal\field\FieldStorageConfigInterface|null
   *   A matching field storage for the destination media type, or NULL if it
   *   cannot be instantiated.
   */
  protected function getMediaSourceFieldStorage() {
    $source_field_name = $this->getDestinationMediaSourceFieldName();
    $preexisting_field_storage = $this->entityTypeManager->getStorage('field_storage_config')->load(implode('.', [
      'media',
      $source_field_name,
    ]));
    if ($preexisting_field_storage) {
      assert($preexisting_field_storage instanceof FieldStorageConfigInterface);
      return $preexisting_field_storage;
    }

    $source_plugin_id = $this->getDestinationMediaSourcePluginId();
    try {
      $media_source_plugin = $this->mediaSourceManager->createInstance($source_plugin_id);
    }
    catch (PluginException $e) {
      // The specified plugin is invalid or missing.
      return NULL;
    }
    assert($media_source_plugin instanceof MediaSourceInterface);
    $source_plugin_definition = $media_source_plugin->getPluginDefinition();
    $field_type = reset($source_plugin_definition['allowed_field_types']);
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => $source_field_name,
      'type' => $field_type,
    ]);
    assert($field_storage instanceof FieldStorageConfigInterface);

    $field_storage->set('settings', $this->fieldTypeManager->getDefaultStorageSettings($field_storage->getType()));

    return $field_storage;
  }

  /**
   * Get the names of the image type fields from the source database.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   * @param bool $field_names_only
   *   Whether only the name of the image fields should be returned. Defaults to
   *   TRUE.
   *
   * @return array
   *   The array of the available image fields.
   */
  protected function getImageFieldData(Connection $connection, $field_names_only = TRUE): array {
    $image_field_query = $connection->select('field_config', 'fs')
      ->fields('fs', ['field_name'])
      ->condition('fs.type', 'image')
      ->condition('fs.active', 1)
      ->condition('fs.deleted', 0)
      ->condition('fs.storage_active', 1)
      ->condition('fi.deleted', 0);
    $image_field_query->join('field_config_instance', 'fi', 'fs.id = fi.field_id');

    if ($field_names_only) {
      return array_keys($image_field_query->execute()->fetchAllAssoc('field_name'));
    }

    $image_field_query->addField('fs', 'data', 'field_storage_data');
    $image_field_query->addField('fi', 'data', 'field_instance_data');

    $image_fields_data = [];
    foreach ($image_field_query->execute()->fetchAll(\PDO::FETCH_ASSOC) as $item) {
      foreach (['field_storage_data', 'field_instance_data'] as $data_key) {
        $item[$data_key] = unserialize($item[$data_key]);
      }
      $image_fields_data[] = $item;
    }

    return $image_fields_data;
  }

  /**
   * Returns alt, title, with and height properties of the specified file.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   * @param string|int $file_id
   *   The ID of the file.
   *
   * @return array
   *   An array of those properties whose value is not empty.
   */
  protected function getImageData(Connection $connection, $file_id): array {
    foreach ($this->getImageFieldData($connection) as $field_name) {
      $field_table_name = "field_data_$field_name";
      $data_query = $connection->select($field_table_name, $field_name);
      $data_query->addField($field_name, "{$field_name}_alt", 'alt');
      $data_query->addField($field_name, "{$field_name}_title", 'title');
      $data_query->addField($field_name, "{$field_name}_height", 'height');
      $data_query->addField($field_name, "{$field_name}_width", 'width');
      $data_query->condition("{$field_name}_fid", $file_id);

      if (!empty($results = $data_query->execute()->fetchAll(\PDO::FETCH_ASSOC))) {
        $result = reset($results);
        return array_filter($result);
      }
    }

    return [];
  }

}
