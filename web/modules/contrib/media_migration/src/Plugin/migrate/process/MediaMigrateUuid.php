<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media_migration\MediaMigrationUuidOracle;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a UUID of not-yet-migrated and existing media items based on file ID.
 *
 * @MigrateProcessPlugin(
 *   id = "media_migrate_uuid"
 * )
 */
class MediaMigrateUuid extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The media UUID oracle.
   *
   * @var \Drupal\media_migration\MediaMigrationUuidOracle
   */
  protected $mediaUuidOracle;

  /**
   * Constructs a MediaMigrateUuid instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\media_migration\MediaMigrationUuidOracle $media_uuid_oracle
   *   The media UUID oracle.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaMigrationUuidOracle $media_uuid_oracle) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaUuidOracle = $media_uuid_oracle;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_migration.media_uuid_oracle')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value) {
      $non_generated_uuid = $this->mediaUuidOracle->getMediaUuid((int) $value, FALSE);
      if (!empty($non_generated_uuid)) {
        return $non_generated_uuid;
      }
      // No UUID was found – lets set the destination property to empty before
      // throwing a skip process exception (this is only required for 9.2.x and
      // below).
      if (version_compare(\Drupal::VERSION, '9.3.0', 'lt')) {
        $row->setEmptyDestinationProperty($destination_property);
      }
      throw new MigrateSkipProcessException();
    }
    // Do not migrate the row if the file ID is empty.
    throw new MigrateSkipRowException();
  }

}
