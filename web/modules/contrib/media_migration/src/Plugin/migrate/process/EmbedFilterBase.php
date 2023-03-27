<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager;
use Drupal\media_migration\MediaMigrationUuidOracleInterface;
use Drupal\media_migration\Traits\MediaLookupTrait;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;

/**
 * Base class for media embed code filter text process plugins.
 */
abstract class EmbedFilterBase extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  use MediaLookupTrait;

  /**
   * The actual migration plugin instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The media UUID oracle.
   *
   * @var \Drupal\media_migration\MediaMigrationUuidOracleInterface
   */
  protected $mediaUuidOracle;

  /**
   * The entity embed display plugin manager service, if available.
   *
   * @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null
   */
  protected $entityEmbedDisplayPluginManager;

  /**
   * Constructs a new EmbedFilterBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\media_migration\MediaMigrationUuidOracleInterface $media_uuid_oracle
   *   The media UUID oracle.
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager|null $entity_embed_display_manager
   *   The entity embed display plugin manager service, if available.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migration lookup service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MediaMigrationUuidOracleInterface $media_uuid_oracle, $entity_embed_display_manager, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migration = $migration;
    $this->mediaUuidOracle = $media_uuid_oracle;
    $this->entityEmbedDisplayPluginManager = $entity_embed_display_manager;
    $this->migrateLookup = $migrate_lookup;
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * Returns the destination display plugin ID.
   *
   * @param string $view_mode
   *   The view_mode from the source.
   * @param string $destination_filter_plugin_id
   *   The transform destination filter plugin ID.
   *
   * @return string
   *   The embed media's display plugin ID or view_mode.
   */
  protected function getDisplayPluginId(string $view_mode, string $destination_filter_plugin_id) {
    switch ($destination_filter_plugin_id) {
      case 'entity_embed':
        $display_plugin_id = "view_mode:media.$view_mode";
        break;

      case 'media_embed':
        return $view_mode;

      default:
        throw new \LogicException();
    }

    // Ensure that the display plugin exists.
    if ($this->entityEmbedDisplayPluginManager instanceof EntityEmbedDisplayManager) {
      $available_plugins = $this->entityEmbedDisplayPluginManager->getDefinitionOptionsForEntityType('media');

      if (empty($available_plugins)) {
        throw new \LogicException("Media Migration cannot replace a media_filter token in a content entity, since there aren't any available entity_embed display plugins.");
      }

      if (!isset($available_plugins[$display_plugin_id])) {
        // If the preselected display plugin does not exist, then we will
        // try to map it to 'view_mode:media.full'.
        if (isset($available_plugins['view_mode:media.full'])) {
          $display_plugin_id = 'view_mode:media.full';
        }
        // If 'view_mode:media.full' is also missing, then we try to pick
        // the first 'view_mode:media.[any]' derivative.
        else {
          $view_mode_plugins = array_reduce(array_keys($available_plugins), function ($carry, $plugin_id) {
            if (strpos($plugin_id, 'view_mode:media.') === 0) {
              $carry[$plugin_id] = $plugin_id;
            }
            return $carry;
          });

          // If we have 'view_mode:media.[any]', we use the first one; if
          // not, then use the first display plugin.
          $display_plugin_id = !empty($view_mode_plugins) ? reset($view_mode_plugins) : array_keys($available_plugins)[0];
        }
      }
    }

    return $display_plugin_id;
  }

}
