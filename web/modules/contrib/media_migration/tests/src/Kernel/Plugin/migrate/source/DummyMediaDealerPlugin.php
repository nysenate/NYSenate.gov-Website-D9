<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media_migration\FileEntityDealerPluginInterface;
use Drupal\migrate\Row;

/**
 * A Dummy media dealer plugin for testing Media Migration.
 */
class DummyMediaDealerPlugin extends PluginBase implements FileEntityDealerPluginInterface {

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
  public function getDestinationMediaTypeIdBase() {
    return $this->pluginDefinition['destination_media_type_id_base'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeLabel() {
    return $this->getDestinationMediaTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaTypeSourceFieldLabel() {
    return $this->getDestinationMediaTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourceFieldName() {
    return $this->getDestinationMediaTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationMediaSourcePluginId() {
    return $this->configuration['destination_media_source_plugin_id'];
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
  public function prepareMediaTypeRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldStorageRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldInstanceRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldWidgetRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void {}

  /**
   * {@inheritdoc}
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void {}

}
