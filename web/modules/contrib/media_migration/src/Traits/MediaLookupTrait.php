<?php

namespace Drupal\media_migration\Traits;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateLookupInterface;

/**
 * Trait looking for migrated media entities.
 */
trait MediaLookupTrait {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Returns the UUID of the migrated media entity, if any.
   *
   * @param string|int $source_id
   *   The source if of the file.
   * @param string[] $migrations
   *   List of file and/or media migrations.
   *
   * @return string|null
   *   The UUID of the migrated media entity, or NULL if it cannot be found.
   */
  protected function getExistingMediaUuid($source_id, array $migrations): ?string {
    if ($destination_id = $this->getMigratedMediaId($source_id, $migrations)) {
      $media = $this->getMediaStorage()->load($destination_id);
      if ($media instanceof MediaInterface) {
        return $media->uuid();
      }
    }
    return NULL;
  }

  /**
   * Returns the ID of the migrated media entity, if any.
   *
   * @param string|int $source_id
   *   The source if of the file.
   * @param string[] $migrations
   *   List of file and/or media migrations.
   *
   * @return string|null
   *   The ID of the migrated media entity, or NULL if it cannot be found.
   */
  protected function getMigratedMediaId(int $source_id, array $migrations): ?string {
    $destination_ids_array = [];
    foreach ($migrations as $migration) {
      try {
        $destination_ids_array = $this->getMigrateLookup()->lookup($migration, [$source_id]);
      }
      catch (\Exception $e) {
      }

      if (!empty($destination_ids_array) && isset(reset($destination_ids_array)['mid'])) {
        break;
      }
    }

    if ($destination_ids_array) {
      return reset($destination_ids_array)['mid'] ?? NULL;
    }

    return NULL;
  }

  /**
   * Returns the migrate lookup service.
   *
   * @return \Drupal\migrate\MigrateLookupInterface
   *   The migrate lookup service.
   */
  private function getMigrateLookup() {
    if (!$this->migrateLookup instanceof MigrateLookupInterface) {
      $this->migrateLookup = \Drupal::service('migrate.lookup');
    }

    return $this->migrateLookup;
  }

  /**
   * Returns the media storage.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The media storage.
   */
  private function getMediaStorage() {
    if (!$this->mediaStorage instanceof ContentEntityStorageInterface) {
      $this->mediaStorage = \Drupal::entityTypeManager()->getStorage('media');
    }

    return $this->mediaStorage;
  }

}
