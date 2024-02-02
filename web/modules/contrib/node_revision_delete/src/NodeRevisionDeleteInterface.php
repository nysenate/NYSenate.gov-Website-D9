<?php

namespace Drupal\node_revision_delete;

/**
 * The Node Revision Delete Interface.
 *
 * @package Drupal\node_revision_delete
 */
interface NodeRevisionDeleteInterface {

  /**
   * Update the max_number for a config name.
   *
   * We need to update the max_number in the existing content type configuration
   * if the new value (max_number) is lower than the actual, in this case the
   * new value will be the value for the content type.
   *
   * @param string $config_name
   *   Config name to update (when_to_delete or minimum_age_to_delete).
   * @param int $max_number
   *   The maximum number for $config_name parameter.
   */
  public function updateTimeMaxNumberConfig(string $config_name, int $max_number): void;

  /**
   * Return the time string for the config_name parameter.
   *
   * @param string $config_name
   *   The config name (minimum_age_to_delete|when_to_delete).
   * @param int $number
   *   The number for the $config_name parameter configuration.
   *
   * @return string
   *   The time string for the $config_name parameter.
   */
  public function getTimeString(string $config_name, int $number): string;

  /**
   * Save the content type config variable.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param int $minimum_revisions_to_keep
   *   Minimum number of revisions to keep.
   * @param int $minimum_age_to_delete
   *   Minimum age in months of revision to delete.
   * @param int $when_to_delete
   *   Number of inactivity months to wait for delete a revision.
   */
  public function saveContentTypeConfig(string $content_type, int $minimum_revisions_to_keep, int $minimum_age_to_delete, int $when_to_delete): void;

  /**
   * Delete the content type config variable.
   *
   * @param string $content_type
   *   Content type machine name.
   */
  public function deleteContentTypeConfig(string $content_type): void;

  /**
   * Return the available values for time frequency.
   *
   * @param string|null $index
   *   The index to retrieve.
   *
   * @return string|array
   *   The index value (human readable value).
   */
  public function getTimeValues(?string $index = NULL);

  /**
   * Return the time option in singular or plural.
   *
   * @param string $time
   *   The time option (days, weeks or months).
   *
   * @return array
   *   The singular and plural for the time.
   */
  public function getTimeNumberString(string $time): array;

  /**
   * Return the list of candidate nodes for node revision delete.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return array
   *   Array of nids.
   */
  public function getCandidatesNodes(string $content_type): array;

  /**
   * Get all revision that are older than current deleted revision.
   *
   * The revisions should have the same language as the current language of the
   * page.
   *
   * @param int $nid
   *   The node id.
   * @param int $currently_deleted_revision_id
   *   The current revision.
   *
   * @return array
   *   An array with the previous revisions.
   */
  public function getPreviousRevisions(int $nid, int $currently_deleted_revision_id): array;

  /**
   * Return the list of candidate revisions to be deleted.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param int $number
   *   The number of revisions to return.
   *
   * @return array
   *   Array of vids.
   */
  public function getCandidatesRevisions(string $content_type, int $number = PHP_INT_MAX): array;

  /**
   * Determine the time value for a node type and a variable type.
   *
   * @param string $config_name
   *   The config name, can by minimum_age_to_delete or when_to_delete.
   * @param int $number
   *   The number representing the variable type.
   *
   * @return int
   *   The timestamp representing the relative time for the node type variable.
   */
  public function getRelativeTime(string $config_name, int $number): int;

  /**
   * Return the configuration for a content type.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return array
   *   An array with the configuration for the content type.
   */
  public function getContentTypeConfig(string $content_type): array;

  /**
   * Return the configuration for a content type with the relative time.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return array
   *   An array with the configuration for the content type.
   */
  public function getContentTypeConfigWithRelativeTime(string $content_type): array;

  /**
   * Get the content types configured for node revision delete.
   *
   * @return array
   *   An array with the configured content types objects.
   */
  public function getConfiguredContentTypes(): array;

  /**
   * Return a number of candidate revisions to be deleted.
   *
   * @param int $number
   *   The number of revisions to return.
   *
   * @return array
   *   Array of vids.
   */
  public function getCandidatesRevisionsByNumber(int $number): array;

  /**
   * Return the revision deletion batch definition.
   *
   * @param array $revisions
   *   The revisions array.
   * @param bool $dry_run
   *   The dry run option.
   *
   * @return array
   *   The batch definition.
   */
  public function getRevisionDeletionBatch(array $revisions, bool $dry_run): array;

  /**
   * Return the candidate revisions to be deleted if a group of nids.
   *
   * All the nids must be of the same content type.
   *
   * @param array $nids
   *   The nids.
   *
   * @return array
   *   Array of vids.
   */
  public function getCandidatesRevisionsByNids(array $nids): array;

}
