<?php

namespace Drupal\media_migration;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Row;

/**
 * Base interface for media dealer plugins.
 */
interface MediaDealerPluginInterface {

  /**
   * Returns the destination media type's ID.
   *
   * @return string
   *   The ID of the destination media type.
   */
  public function getDestinationMediaTypeId();

  /**
   * Returns the destination media type's ID base.
   *
   * @return string
   *   The base ID of the destination media type.
   */
  public function getDestinationMediaTypeIdBase();

  /**
   * Returns the label of the destination media type.
   *
   * @return string
   *   The label of the destination media type.
   */
  public function getDestinationMediaTypeLabel();

  /**
   * Returns the label of the destination media type's source field.
   *
   * @return string
   *   The label of the source field.
   */
  public function getDestinationMediaTypeSourceFieldLabel();

  /**
   * Returns the destination media type's source field name.
   *
   * @return string
   *   The name of the destination media type's source field.
   */
  public function getDestinationMediaSourceFieldName();

  /**
   * Returns the destination media type's source plugin ID.
   *
   * @return string
   *   The ID of the destination media type's source plugin.
   */
  public function getDestinationMediaSourcePluginId();

  /**
   * Alters the definition of the media type migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media type migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaTypeMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Alters the definition of the media source field storage migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media source field
   *   storage migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaSourceFieldStorageMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Alters the definition of the media source field instance migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media source field
   *   instance migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaSourceFieldInstanceMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Alters the definition of the media source field widget settings migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media source field
   *   widget settings migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaSourceFieldWidgetMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Alters the definition of the media field's formatter settings migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media field's formatter
   *   settings migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaFieldFormatterMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Alters the definition of the media entity migration.
   *
   * @param mixed[] $migration_definition
   *   The migration definition of the current derived media entity migration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function alterMediaEntityMigrationDefinition(array &$migration_definition, Connection $connection): void;

  /**
   * Prepares the migration row of a media type.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaTypeRow(Row $row, Connection $connection): void;

  /**
   * Prepares the migration row of a media source field storage.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaSourceFieldStorageRow(Row $row, Connection $connection): void;

  /**
   * Prepares the migration row of a media source field instance.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaSourceFieldInstanceRow(Row $row, Connection $connection): void;

  /**
   * Prepares the migration row of a media field widget configuration.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaSourceFieldWidgetRow(Row $row, Connection $connection): void;

  /**
   * Prepares the migration row of a media field formatter configuration.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaSourceFieldFormatterRow(Row $row, Connection $connection): void;

  /**
   * Prepares the migration row of a media item.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection of the source Drupal 7 instance.
   */
  public function prepareMediaEntityRow(Row $row, Connection $connection): void;

}
