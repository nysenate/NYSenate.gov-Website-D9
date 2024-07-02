<?php

namespace Drupal\media_migration;

/**
 * Interface of Media Migration's UUID oracle.
 */
interface MediaMigrationUuidOracleInterface {

  /**
   * Returns the UUID of a media entity based on its source ID.
   *
   * @param int $source_id
   *   The original ID of the media entity in the source database.
   * @param bool $generate_if_missing
   *   Whether a UUID should be generated if no prophecy was found.
   *
   * @return string|null
   *   The UUID of the given media entity.
   *
   * @throws \LogicException
   */
  public function getMediaUuid(int $source_id, bool $generate_if_missing = TRUE): ?string;

}
