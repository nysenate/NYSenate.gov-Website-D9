<?php

namespace Drupal\migrate_plus\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * SQL table source plugin.
 *
 * Available configuration keys:
 * - table_name: The base table name.
 * - id_fields: Fields used by migrate to identify table rows uniquely.
 * At least one field is required.
 * - fields: (optional) An indexed array of columns present in the specified table.
 * Documents the field names of data provided by the source table.
 *
 * Examples:
 *
 * @code
 *   source:
 *     plugin: table
 *     table_name: colors
 *     id_fields:
 *       color_name:
 *         type: string
 *       hex:
 *         type: string
 *     fields:
 *       color_name: color_name
 *       hex: hex
 * @endcode
 *
 * In this example color data is retrieved from the source
 * table.
 *
 * @code
 *   source:
 *     plugin: table
 *     table_name: autoban
 *     id_fields:
 *       type:
 *         type: string
 *       message:
 *         type: string
 *       threshold:
 *         type: integer
 *       user_type:
 *         type: integer
 *       ip_type:
 *         type: integer
 *       referer:
 *         type: string
 *     fields:
 *       type: type
 *       message: message
 *       threshold: threshold
 *       user_type: user_type
 *       ip_type: ip_type
 *       referer: referer
 * @endcode
 *
 * In this example shows how to retrieve data from autoban source
 * table.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 *
 * @MigrateSource(
 *   id = "table"
 * )
 */
class Table extends SqlBase {

  /**
   * Table alias.
   *
   * @var string
   */
  const TABLE_ALIAS = 't';

  /**
   * The name of the destination table.
   *
   * @var string
   */
  protected $tableName;

  /**
   * IDMap compatible array of id fields.
   *
   * @var array
   */
  protected $idFields;

  /**
   * Array of fields present on the destination table.
   *
   * @var array
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->tableName = $configuration['table_name'];
    // Insert alias in id_fields.
    foreach ($configuration['id_fields'] as &$field) {
      $field['alias'] = static::TABLE_ALIAS;
    }
    $this->idFields = $configuration['id_fields'];
    $this->fields = isset($configuration['fields']) ? $configuration['fields'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select($this->tableName, static::TABLE_ALIAS)->fields(static::TABLE_ALIAS, $this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if (empty($this->idFields)) {
      throw new MigrateException('Id fields are required for a table source');
    }
    return $this->idFields;
  }

}
