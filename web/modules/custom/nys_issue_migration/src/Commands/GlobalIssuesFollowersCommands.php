<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_issue_migration\CanonicalTagMapper;
use Drush\Commands\DrushCommands;

/**
 * Drush command to migrate follow_issue flaggings to follow_global_issue.
 *
 * Purely additive - the old "issues" vocabulary and its follow_issue
 * flaggings are left completely untouched. For every user following an
 * old "issues" term that maps (via CanonicalTagMapper) to a global_issues
 * concept, this creates a follow_global_issue flagging on the
 * corresponding global_issues term, if one doesn't already exist.
 *
 * Usage:
 *   drush global-issues-migrate-followers                  # dry-run
 *   drush global-issues-migrate-followers --commit          # write
 */
class GlobalIssuesFollowersCommands extends DrushCommands {

  const FOLLOW_ISSUE_FLAG = 'follow_issue';
  const FOLLOW_GLOBAL_ISSUE_FLAG = 'follow_global_issue';

  /**
   * Constructs the GlobalIssuesFollowersCommands object.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected Connection $db,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected UuidInterface $uuid,
    protected TimeInterface $time,
  ) {
    parent::__construct();
  }

  /**
   * Migrate follow_issue flaggings to follow_global_issue.
   *
   * Runs as a dry-run by default. Pass --commit to write. Safe to re-run:
   * skips any (user, global_issues term) pair that's already flagged,
   * whether from a prior run of this command or from a user independently
   * following the global issue directly.
   *
   * @command global-issues-migrate-followers
   * @option csv Path to the classification CSV.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush global-issues-migrate-followers
   *   Dry-run: report how many flaggings would be migrated.
   * @usage drush global-issues-migrate-followers --commit
   *   Create the follow_global_issue flaggings.
   */
  public function migrate(
    array $options = [
      'csv' => NULL,
      'commit' => FALSE,
    ],
  ): void {
    $commit = (bool) $options['commit'];
    $csvPath = $options['csv'] ?: (dirname(DRUPAL_ROOT) . '/' . CanonicalTagMapper::DEFAULT_CSV);
    if (!file_exists($csvPath)) {
      $this->io()->error("CSV not found: {$csvPath}");
      return;
    }

    $mapper = new CanonicalTagMapper($this->entityTypeManager);
    $mapper->load($csvPath);

    $sourceTids = $this->db->select('flagging', 'f')
      ->fields('f', ['entity_id'])
      ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
      ->condition('f.entity_type', 'taxonomy_term')
      ->distinct()
      ->execute()
      ->fetchCol();

    $counts = [
      'source_tids_processed' => 0,
      'source_tids_unmapped' => 0,
      'flaggings_migrated' => 0,
      'flaggings_already_exist' => 0,
    ];

    // Dedup (uid, target tid) pairs within this run, across source tids
    // that collapse to the same canonical concept.
    $handled = [];
    $requestTime = $this->time->getRequestTime();

    foreach ($sourceTids as $sourceTid) {
      $targetTid = $mapper->resolve((int) $sourceTid);
      if ($targetTid === NULL) {
        $counts['source_tids_unmapped']++;
        continue;
      }
      $counts['source_tids_processed']++;

      $uids = $this->db->select('flagging', 'f')
        ->fields('f', ['uid'])
        ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('f.entity_type', 'taxonomy_term')
        ->condition('f.entity_id', $sourceTid)
        ->execute()
        ->fetchCol();

      foreach ($uids as $uid) {
        $key = $uid . ':' . $targetTid;
        if (isset($handled[$key])) {
          continue;
        }
        $handled[$key] = TRUE;

        $exists = (bool) $this->db->select('flagging', 'f')
          ->condition('f.flag_id', self::FOLLOW_GLOBAL_ISSUE_FLAG)
          ->condition('f.entity_type', 'taxonomy_term')
          ->condition('f.entity_id', (string) $targetTid)
          ->condition('f.uid', $uid)
          ->countQuery()->execute()->fetchField();

        if ($exists) {
          $counts['flaggings_already_exist']++;
          continue;
        }

        if ($commit) {
          $this->db->insert('flagging')
            ->fields([
              'flag_id' => self::FOLLOW_GLOBAL_ISSUE_FLAG,
              'uuid' => $this->uuid->generate(),
              'entity_type' => 'taxonomy_term',
              'entity_id' => (string) $targetTid,
              'global' => 0,
              'uid' => $uid,
              'session_id' => NULL,
              'created' => $requestTime,
            ])
            ->execute();
        }
        $counts['flaggings_migrated']++;
      }

      if (($counts['source_tids_processed'] % 200) === 0) {
        $this->io()->text("  Processed {$counts['source_tids_processed']} source terms...");
      }
    }

    if ($commit) {
      $this->syncFlagCounts();
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Source tids mapped and processed', number_format($counts['source_tids_processed'])],
        ['Source tids with no canonical mapping (skipped)', number_format($counts['source_tids_unmapped'])],
        ['Flaggings ' . ($commit ? 'migrated' : 'that would migrate'), number_format($counts['flaggings_migrated'])],
        ['Already following the canonical term (no duplicate)', number_format($counts['flaggings_already_exist'])],
      ]
    );

    $unmapped = $mapper->getUnmappedCanonicalNames();
    if ($unmapped) {
      $this->io()->warning('Canonical concept names with no matching global_issues term: ' . implode(', ', $unmapped));
    }

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
    else {
      $this->io()->success(sprintf('Migrated %d flaggings to follow_global_issue.', $counts['flaggings_migrated']));
    }
  }

  /**
   * Rebuilds the flag_counts cache table for follow_global_issue.
   *
   * Direct flagging inserts bypass the Flag API, which normally maintains
   * this cached count table.
   */
  protected function syncFlagCounts(): void {
    $this->db->delete('flag_counts')
      ->condition('flag_id', self::FOLLOW_GLOBAL_ISSUE_FLAG)
      ->execute();

    $counts = $this->db->select('flagging', 'f')
      ->fields('f', ['entity_id'])
      ->condition('f.flag_id', self::FOLLOW_GLOBAL_ISSUE_FLAG)
      ->condition('f.entity_type', 'taxonomy_term')
      ->groupBy('f.entity_id')
      ->execute();

    foreach ($counts as $row) {
      $count = (int) $this->db->select('flagging', 'f')
        ->condition('f.flag_id', self::FOLLOW_GLOBAL_ISSUE_FLAG)
        ->condition('f.entity_type', 'taxonomy_term')
        ->condition('f.entity_id', $row->entity_id)
        ->countQuery()->execute()->fetchField();

      $this->db->insert('flag_counts')
        ->fields([
          'flag_id' => self::FOLLOW_GLOBAL_ISSUE_FLAG,
          'entity_id' => $row->entity_id,
          'entity_type' => 'taxonomy_term',
          'count' => $count,
          'last_updated' => $this->time->getRequestTime(),
        ])
        ->execute();
    }
  }

}
