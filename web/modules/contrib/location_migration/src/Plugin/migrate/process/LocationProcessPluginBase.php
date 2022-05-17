<?php

namespace Drupal\location_migration\Plugin\migrate\process;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for Location Migrate migration process plugins.
 */
abstract class LocationProcessPluginBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The database object of the current migration.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Whether a "location_email" table exists in the source DB.
   *
   * @var bool
   */
  protected $emailTableExists;

  /**
   * Whether a "location_fax" table exists in the source DB.
   *
   * @var bool
   */
  protected $faxTableExists;

  /**
   * Whether a "location_phone" table exists in the source DB.
   *
   * @var bool
   */
  protected $phoneTableExists;

  /**
   * Whether a "location_www" table exists in the source DB.
   *
   * @var bool
   */
  protected $wwwTableExists;

  /**
   * Constructs a new migration process plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $configuration += [
      'entity_type_id' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $source_plugin = $migration->getSourcePlugin();
    assert($source_plugin instanceof DrupalSqlBase);
    $database = $source_plugin->getDatabase();
    $this->database = $database;
    $this->emailTableExists = $database->schema()->tableExists('location_email');
    $this->faxTableExists = $database->schema()->tableExists('location_fax');
    $this->phoneTableExists = $database->schema()->tableExists('location_phone');
    $this->wwwTableExists = $database->schema()->tableExists('location_www');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * Returns location source values of the specified location ID.
   *
   * @param int $lid
   *   The ID of the location.
   *
   * @return array
   *   The source values of the specified location. Might be empty if the
   *   location with the given ID is missing or even when the source database
   *   connection is unreachable.
   */
  protected function getLocationProperties(int $lid): array {
    try {
      $location_data_query = $this->database->select('location', 'l')
        ->fields('l')
        ->condition('l.lid', $lid);

      if ($this->emailTableExists) {
        $location_data_query->leftJoin('location_email', 'email', 'l.lid = email.lid');
        $location_data_query->fields('email', ['email']);
      }
      if ($this->faxTableExists) {
        $location_data_query->leftJoin('location_fax', 'fax', 'l.lid = fax.lid');
        $location_data_query->fields('fax', ['fax']);
      }
      if ($this->phoneTableExists) {
        $location_data_query->leftJoin('location_phone', 'phone', 'l.lid = phone.lid');
        $location_data_query->fields('phone', ['phone']);
      }
      if ($this->wwwTableExists) {
        $location_data_query->leftJoin('location_www', 'www', 'l.lid = www.lid');
        $location_data_query->fields('www', ['www']);
      }

      $location_results = $location_data_query->execute()
        ->fetchAllAssoc('lid', \PDO::FETCH_ASSOC);

      if (count($location_results) === 1) {
        $location_raw = reset($location_results);
        return $location_raw;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
    }

    return [];
  }

  /**
   * Returns location IDs based on the current migration and plugin config.
   *
   * @param mixed $values
   *   The values to be transformed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   *
   * @return int[]
   *   The location IDs for the current process. Empty array if no location ID
   *   was found.
   */
  protected function getLocationIds($values, Row $row): array {
    if ($this->configuration['entity_type_id'] !== NULL) {
      return $this->getEntityLocationIds($row);
    }

    if (is_array($values)) {
      ksort($values);
    }
    return array_reduce($values ?? [], function (array $carry, array $value) {
      if (!empty($value['lid'])) {
        $carry[] = $value['lid'];
      }
      return $carry;
    }, []);
  }

  /**
   * Returns a location ID associated with the entity of the current migration.
   *
   * @param \Drupal\migrate\Row $row
   *   The current migration row.
   *
   * @return int[]
   *   An array of location IDs. An empty array if no value was found for the
   *   given entity.
   */
  protected function getEntityLocationIds(Row $row): array {
    $entity_type_id = $this->configuration['entity_type_id'];
    $supported_entity_type_ids = [
      'node',
      'taxonomy_term',
      'user',
    ];
    if (!in_array($entity_type_id, $supported_entity_type_ids, TRUE)) {
      // The given entity type is not supported by Drupal7 Location module.
      // @todo Consider add some logging.
      throw new MigrateSkipProcessException(sprintf('The given entity type ID "%s" is not supported by Drupal7 Location module.', $entity_type_id));
    }
    $entity_ids = NULL;
    $lid_conditions = NULL;
    switch ($this->configuration['entity_type_id']) {
      case 'taxonomy_term':
        // Pattern:
        // @code
        // <row source property> => <placeholder of the row value in conditions>
        // @endcode
        $entity_ids = [
          'tid' => '@tid@',
        ];
        // Pattern:
        // @code
        // <"location_instances" column name> => <expected value>
        // @endcode
        // Placeholders in the expected value will be replaced with the source
        // row values from the "$entity_ids" array above.
        $lid_conditions = [
          'nid' => 0,
          'vid' => 0,
          'uid' => 0,
          'genid' => 'taxonomy:@tid@',
        ];
        break;

      case 'node':
        $entity_ids = [
          'nid' => '@nid@',
          'vid' => '@vid@',
        ];
        $lid_conditions = [
          'nid' => '@nid@',
          'vid' => '@vid@',
          'uid' => 0,
          'genid' => '',
        ];
        break;

      case 'user':
        $entity_ids = [
          'uid' => '@uid@',
        ];
        $lid_conditions = [
          'nid' => 0,
          'vid' => 0,
          'uid' => '@uid@',
          'genid' => '',
        ];
        break;
    }

    try {
      foreach ($entity_ids as $entity_id_source => $value_alias) {
        unset($entity_ids[$entity_id_source]);
        $pattern = '/' . preg_quote($value_alias, '/') . '/';
        $entity_ids[$pattern] = $row->getSourceProperty($entity_id_source);
      }
      $location_id_query = $this->database->select('location_instance', 'li')
        ->fields('li', ['lid']);
      foreach ($lid_conditions as $column_name => $column_value_raw) {
        $column_value = preg_replace(array_keys($entity_ids), array_values($entity_ids), $column_value_raw);
        $location_id_query->condition("li.$column_name", $column_value);
      }
      $location_id_query->orderBy('li.lid');
      return array_reduce($location_id_query->execute()->fetchAll(\PDO::FETCH_ASSOC), function (array $carry, array $item) {
        $carry[] = (int) $item['lid'];
        return $carry;
      }, []);
    }
    catch (DatabaseExceptionWrapper $e) {
    }

    return [];
  }

}
