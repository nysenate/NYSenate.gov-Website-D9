<?php

namespace Drupal\migrate_upgrade;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\Core\Database\Database;
use Drush\Sql\SqlBase;
use Psr\Log\LoggerInterface;

/**
 * Class MigrateUpgradeDrushRunner.
 *
 * @package Drupal\migrate_upgrade
 */
class MigrateUpgradeDrushRunner {
  use MigrationConfigurationTrait;
  use StringTranslationTrait;

  /**
   * The list of migrations to run and their configuration.
   *
   * @var \Drupal\migrate\Plugin\Migration[]
   */
  protected $migrationList = [];

  /**
   * MigrateMessage instance to display messages during the migration process.
   *
   * @var \Drupal\migrate_upgrade\DrushLogMigrateMessage
   */
  protected static $messages;

  /**
   * The Drupal version being imported.
   *
   * @var string
   */
  protected $version;

  /**
   * The state key used to store database configuration.
   *
   * @var string
   */
  protected $databaseStateKey;

  /**
   * List of D6 node migration IDs we've seen.
   *
   * @var array
   */
  protected $d6NodeMigrations = [];

  /**
   * List of D6 node revision migration IDs we've seen.
   *
   * @var array
   */
  protected $d6RevisionMigrations = [];

  /**
   * Drush options parameters.
   *
   * @var array
   */
  protected $options = [];

  /**
   * List of process plugin IDs used to lookup migrations.
   *
   * @var array
   */
  protected $migrationLookupPluginIds = [
    'migration',
    'migration_lookup',
  ];

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * MigrateUpgradeDrushRunner constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Drush logger compatible with Drupal.
   * @param array $options
   *   Drush options parameters.
   */
  public function __construct(LoggerInterface $logger, array $options = []) {
    $this->logger = $logger;
    $this->setOptions($options);
  }

  /**
   * Set options parameters according to Drush version.
   *
   * @param array $options
   *   Drush options parameters.
   */
  protected function setOptions(array $options = []) {
    $this->options = $options;
    // Drush <= 8.
    if (empty($this->options)) {
      $this->options = [
        'legacy-db-key' => drush_get_option('legacy-db-key'),
        'legacy-db-url' => drush_get_option('legacy-db-url'),
        'legacy-db-prefix' => drush_get_option('legacy-db-prefix'),
        'legacy-root' => drush_get_option('legacy-root'),
        'debug' => drush_get_option('debug'),
        'migration-prefix' => drush_get_option('migration-prefix', 'upgrade_'),
      ];
    }
    $this->options = array_merge([
      'legacy-db-key' => '',
      'legacy-db-url' => '',
      'legacy-db-prefix' => '',
      'legacy-root' => '',
      'debug' => '',
      'migration-prefix' => 'upgrade_',
    ], $this->options);
  }

  /**
   * From the provided source information, configure the appropriate migrations.
   *
   * Configures from the currently active configuration.
   *
   * @throws \Exception
   */
  public function configure() {
    $legacy_db_key = $this->options['legacy-db-key'];
    if (!empty($legacy_db_key)) {
      $connection = Database::getConnection('default', $legacy_db_key);
      $this->version = $this->getLegacyDrupalVersion($connection);
      $database_state['key'] = $legacy_db_key;
      $database_state_key = 'migrate_drupal_' . $this->version;
      \Drupal::state()->set($database_state_key, $database_state);
      \Drupal::state()->set('migrate.fallback_state_key', $database_state_key);
    }
    else {
      $db_url = $this->options['legacy-db-url'];
      $db_prefix = $this->options['legacy-db-prefix'];
      $db_spec = SqlBase::dbSpecFromDbUrl($db_url);
      $db_spec['prefix'] = $db_prefix;
      $connection = $this->getConnection($db_spec);
      $this->version = $this->getLegacyDrupalVersion($connection);
      $this->createDatabaseStateSettings($db_spec, $this->version);
    }

    $this->databaseStateKey = 'migrate_drupal_' . $this->version;
    $migrations = $this->getMigrations($this->databaseStateKey, $this->version);
    $this->migrationList = [];
    foreach ($migrations as $migration) {
      if (strpos($migration->id(), $this->options['migration-prefix']) === 0) {
        continue;
      }
      $this->applyFilePath($migration);
      $this->prefixFileMigration($migration);
      $this->migrationList[$migration->id()] = $migration;
    }
  }

  /**
   * Adds the source base path configuration to relevant migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration to alter with the provided path.
   */
  protected function applyFilePath(MigrationInterface $migration) {
    $destination = $migration->getDestinationConfiguration();
    if ($destination['plugin'] === 'entity:file') {
      // Make sure we have a single trailing slash.
      $source_base_path = rtrim($this->options['legacy-root'], '/') . '/';
      $source = $migration->getSourceConfiguration();
      $source['constants']['source_base_path'] = $source_base_path;
      $migration->set('source', $source);
    }
  }

  /**
   * For D6 file fields, make sure the d6_file migration is prefixed.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration to alter.
   */
  protected function prefixFileMigration(MigrationInterface $migration) {
    $process = $migration->getProcess();
    foreach ($process as $destination => &$plugins) {
      foreach ($plugins as &$plugin) {
        if ($plugin['plugin'] === 'd6_field_file') {
          $file_migration = isset($plugin['migration']) ? $plugin['migration'] : 'd6_file';
          $plugin['migration'] = $this->modifyId($file_migration);
        }
      }
    }
  }

  /**
   * Run the configured migrations.
   *
   * @return array
   *   The executed migration names.
   */
  public function import() {
    $migration_ids = [];
    static::$messages = new DrushLogMigrateMessage($this->logger);
    if ($this->options['debug']) {
      \Drupal::service('event_dispatcher')->addListener(MigrateEvents::IDMAP_MESSAGE,
        [get_class(), 'onIdMapMessage']);
    }
    foreach ($this->migrationList as $migration_id => $migration) {
      $this->logger->log('notice', dt('Upgrading @migration', ['@migration' => $migration_id]));
      $executable = new MigrateExecutable($migration, static::$messages);
      // drush_op() provides --simulate support.
      drush_op([$executable, 'import']);
      $migration_ids[$migration_id] = [
        'original' => $migration_id,
        'generated' => $migration_id,
      ];
    }
    return $migration_ids;
  }

  /**
   * Export the configured migration plugins as configuration entities.
   *
   * @return array
   *   The exported migration names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function export() {
    $migration_ids = [];
    $db_info = \Drupal::state()->get($this->databaseStateKey);

    // Create a group to hold the database configuration.
    $group_details = [
      'id' => $this->databaseStateKey,
      'label' => 'Import from Drupal ' . $this->version,
      'description' => 'Migrations originally generated from drush migrate-upgrade --configure-only',
      'source_type' => 'Drupal ' . $this->version,
      'shared_configuration' => [
        'source' => [
          'key' => 'drupal_' . $this->version,
        ],
      ],
    ];

    // Only add the database connection info to the configuration entity
    // if it was passed in as a parameter.
    if (!empty($this->options['legacy-db-url'])) {
      $group_details['shared_configuration']['source']['database'] = $db_info['database'];
    }

    // Ditto for the key.
    if (!empty($this->options['legacy-db-key'])) {
      $group_details['shared_configuration']['source']['key'] = $this->options['legacy-db-key'];
    }

    // Load existing migration group and update it with changed settings,
    // or create a new one if none exists.
    $group = MigrationGroup::load($group_details['id']);
    if (empty($group)) {
      $group = MigrationGroup::create($group_details);
    }
    else {
      $this->setEntityProperties($group, $group_details);
    }
    $group->save();
    foreach ($this->migrationList as $migration_id => $migration) {
      $migration_details = [];
      $migration_details['id'] = $migration_id;
      $migration_details['label'] = $migration->label();
      $plugin_definition = $migration->getPluginDefinition();
      $migration_details['class'] = $plugin_definition['class'];
      if (isset($plugin_definition['field_plugin_method'])) {
        $migration_details['field_plugin_method'] = $plugin_definition['field_plugin_method'];
      }
      if (isset($plugin_definition['cck_plugin_method'])) {
        $migration_details['cck_plugin_method'] = $plugin_definition['cck_plugin_method'];
      }
      $migration_details['migration_group'] = $this->databaseStateKey;
      $migration_details['migration_tags'] = isset($plugin_definition['migration_tags']) ? $plugin_definition['migration_tags'] : [];
      $migration_details['source'] = $migration->getSourceConfiguration();
      $migration_details['destination'] = $migration->getDestinationConfiguration();
      $migration_details['process'] = $migration->getProcess();
      $migration_details['migration_dependencies'] = $migration->getMigrationDependencies();
      $migration_details = $this->substituteIds($migration_details);
      $migration_entity = Migration::load($migration_details['id']);
      if (empty($migration_entity)) {
        $migration_entity = Migration::create($migration_details);
      }
      else {
        $this->setEntityProperties($migration_entity, $migration_details);
      }
      $migration_entity->save();
      $migration_ids[$migration_entity->id()] = [
        'original' => $migration_id,
        'generated' => $migration_entity->id(),
      ];
    }
    return $migration_ids;
  }

  /**
   * Set entity properties.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to update.
   * @param array $properties
   *   The properties to update.
   */
  protected function setEntityProperties(ConfigEntityInterface $entity, array $properties) {
    foreach ($properties as $key => $value) {
      $entity->set($key, $value);
    }
    foreach ($entity as $property => $value) {
      // Filter out values not in updated properties.
      if (!isset($properties[$property])) {
        $entity->set($property, NULL);
      }
    }
  }

  /**
   * Rewrite any migration plugin IDs so they won't conflict with the core IDs.
   *
   * @param array $entity_array
   *   A configuration array for a migration.
   *
   * @return array
   *   The migration configuration array modified with new IDs.
   */
  protected function substituteIds(array $entity_array) {
    $entity_array['id'] = $this->modifyId($entity_array['id']);
    foreach ($entity_array['migration_dependencies'] as $type => $dependencies) {
      $new_dependencies = [];
      foreach ($dependencies as $dependency) {
        $new_dependencies = array_merge($new_dependencies, array_map([$this, 'modifyId'], $this->expandPluginIds([$dependency])));
      }
      $entity_array['migration_dependencies'][$type] = $new_dependencies;
    }
    $this->substituteMigrationIds($entity_array['process']);
    return $entity_array;
  }

  /**
   * Recursively substitute IDs for migration plugins.
   *
   * @param array|string $process
   *   The process to inspect and substitute.
   */
  protected function substituteMigrationIds(&$process) {
    if (is_array($process)) {
      // We found a migration plugin, change the ID.
      if (isset($process['plugin']) && in_array($process['plugin'], $this->migrationLookupPluginIds)) {
        if (is_array($process['migration'])) {
          $migration_ids = $process['migration'];
        }
        else {
          $migration_ids = [$process['migration']];
        }
        $expanded_migration_ids = $this->expandPluginIds($migration_ids);
        $new_migration_ids = array_map([
          $this,
          'modifyId',
        ], $expanded_migration_ids);
        if (count($new_migration_ids) == 1) {
          $process['migration'] = reset($new_migration_ids);
        }
        else {
          $process['migration'] = $new_migration_ids;
        }
        // The source_ids configuration for migrate_lookup is keyed by
        // migration id.  If it is there, we need to rekey to the new ids.
        if (isset($process['source_ids']) && is_array($process['source_ids'])) {
          $new_source_ids = [];
          foreach ($process['source_ids'] as $migration_id => $source_ids) {
            $new_migration_id = $this->modifyId($migration_id);
            $new_source_ids[$new_migration_id] = $source_ids;
          }
          $process['source_ids'] = $new_source_ids;
        }
      }
      else {
        // Recurse on each array member.
        foreach ($process as &$subprocess) {
          $this->substituteMigrationIds($subprocess);
        }
      }
    }
  }

  /**
   * Modify an ID.
   *
   * @param string $id
   *   The original core plugin ID.
   *
   * @return string
   *   The ID modified to serve as a configuration entity ID.
   */
  protected function modifyId($id) {
    return $this->options['migration-prefix'] . str_replace(':', '_', $id);
  }

  /**
   * Rolls back the configured migrations.
   */
  public function rollback() {
    static::$messages = new DrushLogMigrateMessage($this->logger);
    $database_state_key = \Drupal::state()->get('migrate.fallback_state_key');
    $database_state = \Drupal::state()->get($database_state_key);
    $db_spec = $database_state['database'];
    $connection = $this->getConnection($db_spec);
    $version = $this->getLegacyDrupalVersion($connection);
    $migrations = $this->getMigrations('migrate_drupal_' . $version, $version);

    // Roll back in reverse order.
    $this->migrationList = array_reverse($migrations);

    foreach ($migrations as $migration) {
      $this->logger->log('notice', dt('Rolling back @migration', ['@migration' => $migration->id()]));
      $executable = new MigrateExecutable($migration, static::$messages);
      // drush_op() provides --simulate support.
      drush_op([$executable, 'rollback']);
    }
  }

  /**
   * Expand derivative migration dependencies.
   *
   * We need to expand any derivative migrations. Derivative migrations are
   * calculated by migration derivers such as D6NodeDeriver. This allows
   * migrations to depend on the base id and then have a dependency on all
   * derivative migrations. For example, d6_comment depends on d6_node but after
   * we've expanded the dependencies it will depend on d6_node:page,
   * d6_node:story and so on, for other derivative migrations.
   *
   * @return array
   *   An array of expanded plugin ids.
   */
  protected function expandPluginIds(array $migration_ids) {
    $plugin_ids = [];
    foreach ($migration_ids as $id) {
      $plugin_ids += preg_grep('/^' . preg_quote($id, '/') . PluginBase::DERIVATIVE_SEPARATOR . '/', array_keys($this->migrationList));
      if (array_key_exists($id, $this->migrationList)) {
        $plugin_ids[] = $id;
      }
    }
    return array_values($plugin_ids);
  }

  /**
   * Display any messages being logged to the ID map.
   *
   * @param \Drupal\migrate\Event\MigrateIdMapMessageEvent $event
   *   The message event.
   */
  public static function onIdMapMessage(MigrateIdMapMessageEvent $event) {
    if ($event->getLevel() == MigrationInterface::MESSAGE_NOTICE ||
        $event->getLevel() == MigrationInterface::MESSAGE_INFORMATIONAL) {
      $type = 'status';
    }
    else {
      $type = 'error';
    }
    $source_id_string = implode(',', $event->getSourceIdValues());
    $message = t('Source ID @source_id: @message',
      ['@source_id' => $source_id_string, '@message' => $event->getMessage()]);
    static::$messages->display($message, $type);
  }

}
