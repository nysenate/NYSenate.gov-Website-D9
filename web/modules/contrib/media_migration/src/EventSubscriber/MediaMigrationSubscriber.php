<?php

namespace Drupal\media_migration\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Media migration event subscriber.
 */
class MediaMigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a new MediaMigrationSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MigrationPluginManagerInterface $migration_plugin_manager, MigrateLookupInterface $migrate_lookup) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * Migrate prepare row event handler.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $this->fileEntityFieldToMedia($event);
    $this->imageFieldToMedia($event);
  }

  /**
   * Migrates file entity fields to media ones.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   */
  private function fileEntityFieldToMedia(MigratePrepareRowEvent $event) {
    $row = $event->getRow();
    $source = $event->getSource();

    // Change the type from file to file_entity so it can be processed by
    // a migrate field plugin.
    // @see \Drupal\file_entity_migration\Plugin\migrate\field\FileEntity
    if (in_array($source->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
      'd7_view_mode',
    ])) {
      if ($row->getSourceProperty('type') == 'file') {
        $row->setSourceProperty('type', 'file_entity');
      }
    }

    // Transform entity reference fields pointing to file entities so
    // they point to media ones.
    if (($source->getPluginId() == 'd7_field') && ($row->getSourceProperty('type') == 'entityreference')) {
      $settings = $row->getSourceProperty('settings');
      if ($settings['target_type'] == 'file') {
        $settings['target_type'] = 'media';
        $row->setSourceProperty('settings', $settings);
      }
    }

    // Map path alias sources from file/1234 to media/1234.
    if (($source->getPluginId() == 'd7_url_alias') && (strpos($row->getSourceProperty('source'), 'file/') === 0)) {
      $source_url = preg_replace('/^file/', 'media', $row->getSourceProperty('source'));
      $row->setSourceProperty('source', $source_url);
    }

    // Map redirections from file/1234 to media/1234.
    if (($source->getPluginId() == 'd7_path_redirect') && (strpos($row->getSourceProperty('redirect'), 'file/') === 0)) {
      $redirect = preg_replace('/^file/', 'media', $row->getSourceProperty('redirect'));
      $row->setSourceProperty('redirect', $redirect);
    }

    // Map file menu links to media ones.
    if (($source->getPluginId() == 'menu_link') && (strpos($row->getSourceProperty('link_path'), 'file/') === 0)) {
      $link_path = preg_replace('/^file/', 'media', $row->getSourceProperty('link_path'));
      $row->setSourceProperty('link_path', $link_path);
    }

    // Prevent the migration of the alt and title field configurations for image
    // media type bundles. These properties will be migrated into the image
    // media's source field configuration (which is an image field).
    if (in_array($source->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
    ])) {
      $is_media_config = $row->getSourceProperty('entity_type') === 'file';
      $is_image_bundle = $row->getSourceProperty('bundle') === 'image';
      $special_fields_to_ignore = [
        'field_file_image_alt_text',
        'field_file_image_title_text',
      ];
      $skip_this_field = $is_media_config && ($is_image_bundle || $source->getPluginId() === 'd7_field') && in_array($row->getSourceProperty('field_name'), $special_fields_to_ignore, TRUE);

      if ($skip_this_field) {
        throw new MigrateSkipRowException('Skipping field ' . $row->getSourceProperty('field_name') . " as it will be migrated to the image media entity's source image field.");
      }
    }
  }

  /**
   * Migrates image fields to media image fields.
   *
   * Changes the type from image to media_image so it can be processed by
   * a migrate field plugin.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare row event.
   *
   * @throws \Exception
   *   If the row is empty.
   *
   * @see \Drupal\media_migration\Plugin\migrate\field\MediaImage
   */
  private function imageFieldToMedia(MigratePrepareRowEvent $event) {
    if (in_array($event->getSource()->getPluginId(), [
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_per_view_mode',
      'd7_field_instance_per_form_display',
      'd7_view_mode',
    ])) {
      $row = $event->getRow();
      if (($row->getSourceProperty('type') == 'image')) {
        $row->setSourceProperty('type', 'media_image');
      }
    }
  }

  /**
   * Handles long media source field names.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event.
   */
  public function preImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    if (
      !in_array(
        $migration->getSourcePlugin()->getPluginId(),
        ['d7_file_plain', 'd7_file_entity_item'],
        TRUE
      )
    ) {
      return;
    }

    // Source properties 'source_field_name' and 'source_field_migration' are
    // set in the appropriate migration deriver class. If they are missing, then
    // this might be a custom migration. And then, we don't do anything.
    $source_config = $migration->getSourceConfiguration();
    $source_field_name = $source_config['source_field_name'] ?? NULL;
    $original_source_field_migration_id = $source_config['source_field_migration_id'] ?? NULL;
    if (empty($source_field_name) || empty($original_source_field_migration_id)) {
      return;
    }

    $source_field_migrations = array_filter(
      $this->migrationPluginManager->getDefinitions(),
      function (array $definition) use ($original_source_field_migration_id) {
        return !empty($definition['source']['media_migration_original_id']) &&
          $definition['source']['media_migration_original_id'] === $original_source_field_migration_id;
      }
    );

    // Migrate Upgrade compatibility.
    $maching_source_field_migration_id = $original_source_field_migration_id;
    if (count($source_field_migrations) > 1) {
      // Try to find the actual ID prefix.
      // @see MigrateUpgradeDrushRunner::modifyId
      $migrate_upgrade_id_base = str_replace(':', '_', $source_config['media_migration_original_id']);
      $migrate_upgrade_id_base_quoted = preg_quote($migrate_upgrade_id_base, '/');
      if (preg_match('/^(.+_)' . $migrate_upgrade_id_base_quoted . '$/', $migration->id(), $matches)) {
        // The actually executed media entity migration is a Migrate Plus
        // migration exported with Migrate Upgrade.
        $migrate_upgrade_prefix = preg_replace('/^(.+_)' . $migrate_upgrade_id_base_quoted . '$/', '$1', $migration->id());
        $source_field_mu_id_base = str_replace(':', '_', $original_source_field_migration_id);
        $maching_source_field_migration_id = $migrate_upgrade_prefix . $source_field_mu_id_base;
      }
    }
    $source_field_migration_def = $source_field_migrations[$maching_source_field_migration_id];
    // The migration of the source field cannot be found. Maybe we're dealing
    // with a custom migration?..
    if ($source_field_migration_def === FALSE) {
      return;
    }

    $sfm_source_conf = $source_field_migration_def['source'];
    $lookup_ids = array_key_exists('types', $sfm_source_conf)
      ? [$sfm_source_conf['types'], $sfm_source_conf['schemes']]
      : [$sfm_source_conf['mimes'], $sfm_source_conf['schemes']];
    try {
      $final_source_field_name = $this->migrateLookup->lookup(
        [$maching_source_field_migration_id],
        $lookup_ids
      );
    }
    catch (PluginException $e) {
    }
    catch (MigrateException $e) {
    }

    if (empty($final_source_field_name[0]['field_name'])) {
      throw new MigrateException(
        sprintf(
          "Cannot identify the the media entity's source field name"
        )
      );
    }
    $final_source_field_name = $final_source_field_name[0]['field_name'];

    if ($source_field_name !== $final_source_field_name) {
      $processes = $migration->getProcess();
      $source_field_escaped = preg_quote($source_field_name, '/');
      $source_field_regex = "/^({$source_field_escaped})([\/]|$)/";
      foreach ($processes as $destination => $process) {
        if (preg_match($source_field_regex, $destination)) {
          $destination_new = preg_replace($source_field_regex, "{$final_source_field_name}$2", $destination);
          $processes[$destination_new] = $process;
          unset($processes[$destination]);
        }
      }

      $migration->setProcess($processes);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigratePlusEvents::PREPARE_ROW => ['onPrepareRow'],
      MigrateEvents::PRE_IMPORT => ['preImport'],
    ];
  }

}
