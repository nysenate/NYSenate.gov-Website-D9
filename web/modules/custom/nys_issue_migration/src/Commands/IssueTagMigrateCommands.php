<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the NYS issue tag consolidation migration.
 *
 * Usage:
 *   drush issue-tag-migrate --stage=create-terms           # dry-run
 *   drush issue-tag-migrate --stage=create-terms --commit  # write
 *   drush issue-tag-migrate --stage=content --commit
 *   drush issue-tag-migrate --stage=followers --commit
 *   drush issue-tag-migrate --stage=delete --commit
 */
class IssueTagMigrateCommands extends DrushCommands {

  const LOG_TABLE = 'nys_issue_migration_log';
  const ISSUES_VOCABULARY = 'issues';
  const FOLLOW_ISSUE_FLAG = 'follow_issue';
  const BATCH_SIZE = 500;
  const VALID_STAGES = ['create-terms', 'content', 'followers', 'delete'];
  const DEFAULT_MAIN_CSV = 'sites/default/files/issue-tags/tag_classification_final_2026-08-06.csv';
  const DEFAULT_TERMS_CSV = 'sites/default/files/issue-tags/terms_to_create.csv';

  /**
   * Constructs the IssueTagMigrateCommands object.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   */
  public function __construct(
    protected Connection $db,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected UuidInterface $uuid,
  ) {
    parent::__construct();
  }

  /**
   * Migrate NYS issue taxonomy tags to canonical terms.
   *
   * Runs as a dry-run by default. Pass --commit to write changes.
   * Stages must be run in order: create-terms → content → followers → delete.
   *
   * @command issue-tag-migrate
   * @option stage Stage to run: create-terms, content, followers, delete.
   * @option commit Write changes. Without this flag, reports counts only.
   * @option csv Path to the main classification CSV.
   * @option terms-csv Path to terms_to_create.csv (create-terms stage only).
   * @usage drush issue-tag-migrate --stage=create-terms
   *   Dry-run: report which canonical terms would be created.
   * @usage drush issue-tag-migrate --stage=create-terms --commit
   *   Create new canonical taxonomy terms.
   * @usage drush issue-tag-migrate --stage=content --commit
   *   Migrate node field_issues references to canonical terms.
   * @usage drush issue-tag-migrate --stage=followers --commit
   *   Migrate follow_issue flaggings to canonical terms.
   * @usage drush issue-tag-migrate --stage=delete --commit
   *   Delete source taxonomy terms.
   */
  public function migrate(array $options = [
    'stage'     => NULL,
    'commit'    => FALSE,
    'csv'       => NULL,
    'terms-csv' => NULL,
  ]): void {
    $stage = $options['stage'];
    $commit = (bool) $options['commit'];
    $csvPath = $options['csv'] ?: (DRUPAL_ROOT . '/' . self::DEFAULT_MAIN_CSV);
    $termsCsvPath = $options['terms-csv'] ?: (DRUPAL_ROOT . '/' . self::DEFAULT_TERMS_CSV);

    if (!$stage || !in_array($stage, self::VALID_STAGES)) {
      throw new \InvalidArgumentException(sprintf(
        '--stage is required. Valid values: %s',
        implode(', ', self::VALID_STAGES)
      ));
    }

    if ($stage === 'create-terms' && !file_exists($termsCsvPath)) {
      throw new \RuntimeException("Terms CSV not found: $termsCsvPath");
    }

    if ($stage !== 'create-terms' && !file_exists($csvPath)) {
      throw new \RuntimeException("Classification CSV not found: $csvPath");
    }

    $this->ensureLogTable();

    $mode = $commit ? 'COMMIT' : 'DRY RUN';
    $this->io()->title("NYS Issue Tag Migration — Stage: $stage [$mode]");

    if (!$commit) {
      $this->io()->caution('Dry-run mode. No changes will be written. Pass --commit to apply.');
    }

    if ($stage === 'create-terms') {
      $termsRows = $this->parseCsv($termsCsvPath);
      $this->io()->text(sprintf('Terms CSV loaded: %d rows from %s', count($termsRows), basename($termsCsvPath)));
      $this->io()->newLine();
      $this->stageCreateTerms($termsRows, $commit);
    }
    else {
      $rows = $this->parseCsv($csvPath);
      $this->io()->text(sprintf('CSV loaded: %d rows from %s', count($rows), basename($csvPath)));
      $this->io()->newLine();
      match ($stage) {
        'content'   => $this->stageContent($rows, $commit),
        'followers' => $this->stageFollowers($rows, $commit),
        'delete'    => $this->stageDelete($rows, $commit),
      };
    }
  }

  // ---------------------------------------------------------------------------
  // Stage 1: Create new canonical terms
  // ---------------------------------------------------------------------------

  /**
   * Creates canonical taxonomy terms from terms_to_create.csv.
   *
   * These are terms with no existing tid in the vocabulary. Rows in the main
   * CSV whose canonical_tid is blank will resolve to these newly created terms
   * via the name-lookup fallback in resolveCanonicalTidForRow().
   */
  private function stageCreateTerms(array $rows, bool $commit): void {
    $counts = ['created' => 0, 'exists' => 0];

    foreach ($rows as $row) {
      $termName = trim($row['canonical_term'] ?? '');
      if ($termName === '') {
        continue;
      }

      $existingTid = $this->db->select('taxonomy_term_field_data', 't')
        ->fields('t', ['tid'])
        ->condition('t.vid', self::ISSUES_VOCABULARY)
        ->condition('t.name', $termName)
        ->orderBy('t.tid', 'ASC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($existingTid) {
        $counts['exists']++;
        $this->io()->text("  EXISTS (tid $existingTid): $termName");
        $this->writeLog('create-terms', 0, $termName, 'term_exists', (int) $existingTid, $termName, NULL, !$commit);
        continue;
      }

      $newTid = NULL;
      if ($commit) {
        $term = Term::create(['vid' => self::ISSUES_VOCABULARY, 'name' => $termName]);
        $term->save();
        $newTid = (int) $term->id();
      }
      $counts['created']++;
      $this->io()->text(sprintf('  %s (tid %s): %s', $commit ? 'CREATED' : 'WOULD CREATE', $newTid ?? 'N/A', $termName));
      $this->writeLog('create-terms', 0, $termName, $commit ? 'term_created' : 'term_would_create', $newTid, $termName, NULL, !$commit);
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Terms created', number_format($counts['created'])],
        ['Already existing (skipped)', number_format($counts['exists'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to create these terms.');
    }
    else {
      $this->io()->success(sprintf('Created %d canonical terms.', $counts['created']));
    }
  }

  // ---------------------------------------------------------------------------
  // Stage 2: Migrate node field_issues references
  // ---------------------------------------------------------------------------

  /**
   * Re-tags nodes from source tids to canonical tids.
   *
   * Only processes merge_to_canonical rows. already_canonical and delete rows
   * are excluded by the disposition filter — not by runtime checks.
   *
   * For each node referencing a source tid:
   * - If the node already has the canonical tid: remove the source ref (dedup).
   * - If not: update the source ref in-place to the canonical tid.
   * Both node__field_issues and node_revision__field_issues are updated.
   */
  private function stageContent(array $rows, bool $commit): void {
    $mergeRows = array_filter($rows, fn($r) => $r['final_disposition'] === 'merge_to_canonical');

    // Build the stage-1 log map before processing. Needed for the ~211 rows
    // whose canonical_tid was blank at CSV generation time (they target terms
    // from terms_to_create.csv). Pre-flight checks every distinct canonical_term
    // name referenced by those rows against the map — catches partial creation
    // (e.g. 7 of 8 terms created) just as hard as zero creation.
    $createdTermMap = $this->buildCreatedTermMap();
    $blankTidRows = array_filter($mergeRows, fn($r) => trim($r['canonical_tid'] ?? '') === '');
    if (!empty($blankTidRows)) {
      $missingTerms = [];
      foreach ($blankTidRows as $r) {
        $name = trim($r['canonical_term'] ?? '');
        if ($name !== '' && !isset($createdTermMap[$name])) {
          $missingTerms[$name] = TRUE;
        }
      }
      if (!empty($missingTerms)) {
        throw new \RuntimeException(sprintf(
          '%d merge_to_canonical rows target %d term(s) not yet in the created-term map. Run --stage=create-terms --commit first, then re-run this stage. Missing: %s',
          count($blankTidRows),
          count($missingTerms),
          implode(', ', array_keys($missingTerms))
        ));
      }
    }

    $counts = [
      'tids_processed'     => 0,
      'tids_already_clean' => 0,
      'tids_data_drift'    => 0,
      'nodes_retagged'     => 0,
      'nodes_deduped'      => 0,
    ];

    $beforeCounts = $commit ? $this->snapshotCanonicalCounts($rows, $createdTermMap) : [];

    foreach ($mergeRows as $row) {
      $sourceTid = (int) $row['tid'];

      if (!$this->tidExistsInDb($sourceTid)) {
        $counts['tids_data_drift']++;
        $this->writeLog('content', $sourceTid, $row['name'], 'data_drift', NULL, NULL, 'tid not found in taxonomy_term_field_data', !$commit);
        continue;
      }

      $canonicalTid = $this->resolveCanonicalTidForRow($row, $createdTermMap);
      if (!$canonicalTid) {
        // Pre-flight should have caught this. Reaching here means a row has a
        // blank canonical_tid with a blank or unrecognised canonical_term —
        // a malformed CSV row. Throw rather than skip to avoid silent data loss.
        throw new \RuntimeException(sprintf(
          'tid %d (%s): canonical_tid could not be resolved and was not caught by pre-flight. canonical_term="%s". Check the CSV for malformed rows.',
          $sourceTid,
          $row['name'],
          $row['canonical_term'] ?? ''
        ));
      }

      $totalRefs = (int) $this->db->select('node__field_issues', 'f')
        ->condition('f.field_issues_target_id', $sourceTid)
        ->countQuery()->execute()->fetchField();

      if ($totalRefs === 0) {
        $counts['tids_already_clean']++;
        $counts['tids_processed']++;
        $this->writeLog('content', $sourceTid, $row['name'], 'already_clean', $canonicalTid, $row['canonical_term'], 'No node references remain', !$commit);
        continue;
      }

      $retagged = 0;
      $deduped = 0;

      if ($commit) {
        while (TRUE) {
          $entityIds = $this->db->select('node__field_issues', 'f')
            ->fields('f', ['entity_id'])
            ->condition('f.field_issues_target_id', $sourceTid)
            ->range(0, self::BATCH_SIZE)
            ->execute()
            ->fetchCol();

          if (empty($entityIds)) {
            break;
          }

          foreach ($entityIds as $entityId) {
            $hasCanonical = (bool) $this->db->select('node__field_issues', 'f')
              ->condition('f.entity_id', $entityId)
              ->condition('f.field_issues_target_id', $canonicalTid)
              ->countQuery()->execute()->fetchField();

            if ($hasCanonical) {
              $this->db->delete('node__field_issues')
                ->condition('entity_id', $entityId)
                ->condition('field_issues_target_id', $sourceTid)
                ->execute();
              $this->db->delete('node_revision__field_issues')
                ->condition('entity_id', $entityId)
                ->condition('field_issues_target_id', $sourceTid)
                ->execute();
              $deduped++;
            }
            else {
              $this->db->update('node__field_issues')
                ->fields(['field_issues_target_id' => $canonicalTid])
                ->condition('entity_id', $entityId)
                ->condition('field_issues_target_id', $sourceTid)
                ->execute();
              $this->db->update('node_revision__field_issues')
                ->fields(['field_issues_target_id' => $canonicalTid])
                ->condition('entity_id', $entityId)
                ->condition('field_issues_target_id', $sourceTid)
                ->execute();
              $retagged++;
            }
          }
        }
      }
      else {
        $subquery = $this->db->select('node__field_issues', 'f2')
          ->fields('f2', ['entity_id'])
          ->condition('f2.field_issues_target_id', $canonicalTid);
        $deduped = (int) $this->db->select('node__field_issues', 'f')
          ->condition('f.field_issues_target_id', $sourceTid)
          ->condition('f.entity_id', $subquery, 'IN')
          ->countQuery()->execute()->fetchField();
        $retagged = $totalRefs - $deduped;
      }

      $counts['tids_processed']++;
      $counts['nodes_retagged'] += $retagged;
      $counts['nodes_deduped'] += $deduped;

      $this->writeLog(
        'content', $sourceTid, $row['name'],
        $commit ? 'nodes_migrated' : 'nodes_would_migrate',
        $canonicalTid, $row['canonical_term'],
        "retagged:$retagged, deduped:$deduped",
        !$commit
      );

      if (($counts['tids_processed'] % 100) === 0) {
        $this->io()->text("  Processed {$counts['tids_processed']} source terms...");
      }
    }

    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Source tids processed', number_format($counts['tids_processed'])],
        ['Nodes retagged to canonical', number_format($counts['nodes_retagged'])],
        ['Nodes deduped (canonical already present)', number_format($counts['nodes_deduped'])],
        ['Source tids already clean (no refs)', number_format($counts['tids_already_clean'])],
        ['Data drift (tid not in DB)', number_format($counts['tids_data_drift'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to migrate node references.');
    }
    else {
      $this->verificationReport($rows, $beforeCounts, 'content', $createdTermMap);
      $this->io()->success(sprintf(
        'Migrated %s nodes (%s retagged, %s deduped).',
        number_format($counts['nodes_retagged'] + $counts['nodes_deduped']),
        number_format($counts['nodes_retagged']),
        number_format($counts['nodes_deduped'])
      ));
    }
  }

  // ---------------------------------------------------------------------------
  // Stage 3: Migrate follow_issue flaggings
  // ---------------------------------------------------------------------------

  /**
   * Migrates follow_issue flaggings from source tids to canonical tids.
   *
   * Only processes merge_to_canonical rows. For each flagging on a source tid:
   * - If the user already follows the canonical term: remove source flagging only.
   * - If not: create a new flagging on the canonical tid, then remove the source.
   */
  private function stageFollowers(array $rows, bool $commit): void {
    $mergeRows = array_filter($rows, fn($r) => $r['final_disposition'] === 'merge_to_canonical');
    $requestTime = \Drupal::time()->getRequestTime();

    // Same dependency check as stageContent: verify every distinct canonical_term
    // referenced by blank-canonical_tid rows is in the created-term map.
    $createdTermMap = $this->buildCreatedTermMap();
    $blankTidRows = array_filter($mergeRows, fn($r) => trim($r['canonical_tid'] ?? '') === '');
    if (!empty($blankTidRows)) {
      $missingTerms = [];
      foreach ($blankTidRows as $r) {
        $name = trim($r['canonical_term'] ?? '');
        if ($name !== '' && !isset($createdTermMap[$name])) {
          $missingTerms[$name] = TRUE;
        }
      }
      if (!empty($missingTerms)) {
        throw new \RuntimeException(sprintf(
          '%d merge_to_canonical rows target %d term(s) not yet in the created-term map. Run --stage=create-terms --commit first, then re-run this stage. Missing: %s',
          count($blankTidRows),
          count($missingTerms),
          implode(', ', array_keys($missingTerms))
        ));
      }
    }

    $counts = [
      'tids_processed'          => 0,
      'tids_data_drift'         => 0,
      'flaggings_migrated'      => 0,
      'flaggings_already_exist' => 0,
      'source_flaggings_removed' => 0,
    ];

    $beforeCounts = $commit ? $this->snapshotCanonicalCounts($rows, $createdTermMap) : [];

    foreach ($mergeRows as $row) {
      $sourceTid = (int) $row['tid'];

      if (!$this->tidExistsInDb($sourceTid)) {
        $counts['tids_data_drift']++;
        $this->writeLog('followers', $sourceTid, $row['name'], 'data_drift', NULL, NULL, 'tid not found in taxonomy_term_field_data', !$commit);
        continue;
      }

      $canonicalTid = $this->resolveCanonicalTidForRow($row, $createdTermMap);
      if (!$canonicalTid) {
        throw new \RuntimeException(sprintf(
          'tid %d (%s): canonical_tid could not be resolved and was not caught by pre-flight. canonical_term="%s". Check the CSV for malformed rows.',
          $sourceTid,
          $row['name'],
          $row['canonical_term'] ?? ''
        ));
      }

      $sourceFlaggings = $this->db->select('flagging', 'f')
        ->fields('f', ['id', 'uid', 'created'])
        ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('f.entity_type', 'taxonomy_term')
        ->condition('f.entity_id', (string) $sourceTid)
        ->execute()
        ->fetchAll();

      $migrated = 0;
      $alreadyExist = 0;

      foreach ($sourceFlaggings as $flagging) {
        $existsOnCanonical = (bool) $this->db->select('flagging', 'f')
          ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
          ->condition('f.entity_type', 'taxonomy_term')
          ->condition('f.entity_id', (string) $canonicalTid)
          ->condition('f.uid', $flagging->uid)
          ->countQuery()->execute()->fetchField();

        if ($commit) {
          if (!$existsOnCanonical) {
            $this->db->insert('flagging')
              ->fields([
                'flag_id'     => self::FOLLOW_ISSUE_FLAG,
                'uuid'        => $this->uuid->generate(),
                'entity_type' => 'taxonomy_term',
                'entity_id'   => (string) $canonicalTid,
                'global'      => 0,
                'uid'         => $flagging->uid,
                'session_id'  => NULL,
                'created'     => $requestTime,
              ])
              ->execute();
            $migrated++;
          }
          else {
            $alreadyExist++;
          }
          $this->db->delete('flagging')
            ->condition('id', $flagging->id)
            ->execute();
        }
        else {
          $existsOnCanonical ? $alreadyExist++ : $migrated++;
        }
      }

      $counts['tids_processed']++;
      $counts['flaggings_migrated'] += $migrated;
      $counts['flaggings_already_exist'] += $alreadyExist;
      $counts['source_flaggings_removed'] += count($sourceFlaggings);

      $this->writeLog(
        'followers', $sourceTid, $row['name'],
        $commit ? 'flaggings_migrated' : 'flaggings_would_migrate',
        $canonicalTid, $row['canonical_term'],
        "migrated:$migrated, already_on_canonical:$alreadyExist, source_removed:" . count($sourceFlaggings),
        !$commit
      );
    }

    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Source tids processed', number_format($counts['tids_processed'])],
        ['Flaggings migrated to canonical', number_format($counts['flaggings_migrated'])],
        ['Flaggings already on canonical (no duplicate created)', number_format($counts['flaggings_already_exist'])],
        ['Source flaggings removed', number_format($counts['source_flaggings_removed'])],
        ['Data drift (tid not in DB)', number_format($counts['tids_data_drift'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to migrate flaggings.');
    }
    else {
      // Sync flag_counts for all canonical tids and remove stale source entries.
      // Direct flagging inserts bypass the Flag API, which normally maintains
      // this cached count table — so we must update it ourselves.
      $this->syncFlagCounts($rows, $createdTermMap);
      $this->verificationReport($rows, $beforeCounts, 'followers', $createdTermMap);
      $this->io()->success(sprintf(
        'Migrated %s flaggings. %s source flaggings removed.',
        number_format($counts['flaggings_migrated']),
        number_format($counts['source_flaggings_removed'])
      ));
    }
  }

  // ---------------------------------------------------------------------------
  // Stage 4: Delete source terms
  // ---------------------------------------------------------------------------

  /**
   * Deletes source taxonomy terms.
   *
   * Hard filter: already_canonical rows are never touched — excluded by
   * disposition column, not by any runtime name or tid check.
   *
   * merge_to_canonical rows: verifies no remaining node references before
   * deleting. Skips with a warning if references remain (run content first).
   *
   * delete rows: removes any remaining node references and flaggings, then
   * deletes the term.
   *
   * A pre-delete manifest is always printed before any deletions execute,
   * even in --commit mode, so the scope is visible before it's irreversible.
   */
  private function stageDelete(array $rows, bool $commit): void {
    // Hard filter on disposition — already_canonical never enters this stage.
    $actionableRows = array_filter(
      $rows,
      fn($r) => $r['final_disposition'] === 'merge_to_canonical' || $r['final_disposition'] === 'delete'
    );

    // ---------------------------------------------------------------------------
    // Pre-delete manifest — always produced, even in --commit mode.
    // ---------------------------------------------------------------------------
    $this->io()->section('Pre-Delete Manifest');

    $manifestMerge = ['to_delete' => 0, 'skipped_refs' => 0, 'not_found' => 0];
    $manifestDelete = ['to_delete' => 0, 'not_found' => 0];
    $skippedRows = [];

    foreach ($actionableRows as $row) {
      $sourceTid = (int) $row['tid'];
      if (!$this->tidExistsInDb($sourceTid)) {
        $row['final_disposition'] === 'merge_to_canonical'
          ? $manifestMerge['not_found']++
          : $manifestDelete['not_found']++;
        continue;
      }

      if ($row['final_disposition'] === 'merge_to_canonical') {
        $remainingRefs = (int) $this->db->select('node__field_issues', 'f')
          ->condition('f.field_issues_target_id', $sourceTid)
          ->countQuery()->execute()->fetchField();
        if ($remainingRefs > 0) {
          $manifestMerge['skipped_refs']++;
          $skippedRows[] = [$sourceTid, $row['name'], $remainingRefs];
        }
        else {
          $manifestMerge['to_delete']++;
        }
      }
      else {
        $manifestDelete['to_delete']++;
      }
    }

    $this->io()->table(
      ['Category', 'Count'],
      [
        ['merge_to_canonical — will delete (refs already migrated)', number_format($manifestMerge['to_delete'])],
        ['merge_to_canonical — SKIPPED (still has node refs, run content first)', number_format($manifestMerge['skipped_refs'])],
        ['merge_to_canonical — not in DB (already deleted)', number_format($manifestMerge['not_found'])],
        ['delete-disposition — will delete (refs/flaggings will be dropped)', number_format($manifestDelete['to_delete'])],
        ['delete-disposition — not in DB (already deleted)', number_format($manifestDelete['not_found'])],
        ['already_canonical — excluded (hard filter, never touched)', number_format(count(array_filter($rows, fn($r) => $r['final_disposition'] === 'already_canonical')))],
      ]
    );

    if (!empty($skippedRows)) {
      $this->io()->warning(count($skippedRows) . ' merge_to_canonical terms still have node references and will be skipped:');
      $display = array_slice($skippedRows, 0, 20);
      $this->io()->table(['TID', 'Name', 'Remaining refs'], $display);
      if (count($skippedRows) > 20) {
        $this->io()->text(sprintf('  ... and %d more. Run content stage first to clear all references.', count($skippedRows) - 20));
      }
    }

    if (!$commit) {
      $this->io()->note('Run with --commit to execute deletions.');
      foreach ($actionableRows as $row) {
        $this->writeLog('delete', (int) $row['tid'], $row['name'], 'term_would_delete', NULL, $row['canonical_term'] ?? NULL, "disposition:{$row['final_disposition']}", TRUE);
      }
      return;
    }

    // ---------------------------------------------------------------------------
    // Execute deletions.
    // ---------------------------------------------------------------------------
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $counts = [
      'deleted'           => 0,
      'not_found'         => 0,
      'skipped_has_refs'  => 0,
      'node_refs_removed' => 0,
      'flaggings_dropped' => 0,
    ];
    $deletedTids = [];

    foreach ($actionableRows as $row) {
      $sourceTid   = (int) $row['tid'];
      $disposition = $row['final_disposition'];

      if (!$this->tidExistsInDb($sourceTid)) {
        $counts['not_found']++;
        $this->writeLog('delete', $sourceTid, $row['name'], 'data_drift', NULL, NULL, 'tid not found — already deleted', FALSE);
        continue;
      }

      if ($disposition === 'merge_to_canonical') {
        $remainingRefs = (int) $this->db->select('node__field_issues', 'f')
          ->condition('f.field_issues_target_id', $sourceTid)
          ->countQuery()->execute()->fetchField();

        if ($remainingRefs > 0) {
          $counts['skipped_has_refs']++;
          $this->writeLog('delete', $sourceTid, $row['name'], 'skipped_has_refs', NULL, $row['canonical_term'], "remaining_refs:$remainingRefs", FALSE);
          continue;
        }

        $term = $storage->load($sourceTid);
        if ($term) {
          $term->delete();
        }
        $counts['deleted']++;
        $deletedTids[] = $sourceTid;
        $this->writeLog('delete', $sourceTid, $row['name'], 'term_deleted', NULL, $row['canonical_term'], 'merge_to_canonical', FALSE);
      }
      elseif ($disposition === 'delete') {
        $nodeRefsCount = (int) $this->db->select('node__field_issues', 'f')
          ->condition('f.field_issues_target_id', $sourceTid)
          ->countQuery()->execute()->fetchField();
        $flaggingsCount = (int) $this->db->select('flagging', 'f')
          ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
          ->condition('f.entity_type', 'taxonomy_term')
          ->condition('f.entity_id', (string) $sourceTid)
          ->countQuery()->execute()->fetchField();

        if ($nodeRefsCount > 0) {
          $this->db->delete('node__field_issues')
            ->condition('field_issues_target_id', $sourceTid)
            ->execute();
          $this->db->delete('node_revision__field_issues')
            ->condition('field_issues_target_id', $sourceTid)
            ->execute();
        }
        if ($flaggingsCount > 0) {
          $this->db->delete('flagging')
            ->condition('flag_id', self::FOLLOW_ISSUE_FLAG)
            ->condition('entity_type', 'taxonomy_term')
            ->condition('entity_id', (string) $sourceTid)
            ->execute();
        }
        $term = $storage->load($sourceTid);
        if ($term) {
          $term->delete();
        }
        $counts['deleted']++;
        $deletedTids[] = $sourceTid;
        $counts['node_refs_removed'] += $nodeRefsCount;
        $counts['flaggings_dropped'] += $flaggingsCount;
        $this->writeLog('delete', $sourceTid, $row['name'], 'term_deleted', NULL, NULL, "delete_disposition, node_refs_removed:$nodeRefsCount, flaggings_dropped:$flaggingsCount", FALSE);
      }

      if (($counts['deleted'] % 500) === 0 && $counts['deleted'] > 0) {
        $this->io()->text("  Deleted {$counts['deleted']} terms so far...");
      }
    }

    // Remove flag_counts rows for every deleted term. delete-disposition terms
    // had their flaggings dropped above but flag_counts is a separate table the
    // Flag API maintains — direct flagging deletes don't touch it automatically.
    // merge_to_canonical terms should have been cleaned by syncFlagCounts() in
    // the followers stage, but we include them here for idempotency.
    foreach (array_chunk($deletedTids, 500) as $chunk) {
      $this->db->delete('flag_counts')
        ->condition('flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('entity_type', 'taxonomy_term')
        ->condition('entity_id', array_map('strval', $chunk), 'IN')
        ->execute();
    }

    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Terms deleted', number_format($counts['deleted'])],
        ['Terms not in DB (already gone)', number_format($counts['not_found'])],
        ['Merge terms skipped — still have node refs', number_format($counts['skipped_has_refs'])],
        ['Node references removed (delete-disposition terms)', number_format($counts['node_refs_removed'])],
        ['Flaggings dropped (delete-disposition terms)', number_format($counts['flaggings_dropped'])],
      ]
    );

    $this->io()->success(sprintf('Deleted %s source taxonomy terms.', number_format($counts['deleted'])));
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Resolves the canonical tid for a CSV row.
   *
   * Primary: reads canonical_tid directly from the row. This covers all rows
   * where the canonical term already existed at CSV generation time.
   *
   * Fallback: for the ~211 merge_to_canonical rows pointing to terms from
   * terms_to_create.csv (those terms had no tid when the CSV was generated, so
   * canonical_tid is blank), looks up by canonical_term name in $createdTermMap
   * — a map built exclusively from stage 1's committed log entries. Returns
   * NULL if the name is not in the map, which means stage 1 --commit has not
   * been run yet. Callers treat NULL as a hard error for those rows.
   *
   * There is intentionally no live DB name search here. Using the log table
   * instead of a SELECT-by-name ensures the lookup is scoped to exactly what
   * stage 1 created and cannot silently match an unrelated term.
   */
  private function resolveCanonicalTidForRow(array $row, array $createdTermMap): ?int {
    $tidValue = trim($row['canonical_tid'] ?? '');
    if ($tidValue !== '') {
      return (int) $tidValue;
    }

    $canonicalName = trim($row['canonical_term'] ?? '');
    if ($canonicalName === '') {
      return NULL;
    }

    return $createdTermMap[$canonicalName] ?? NULL;
  }

  /**
   * Builds the name→tid map for newly created canonical terms.
   *
   * Reads from the migration log table (stage='create-terms', committed only).
   * Used by stages 2 and 3 to resolve the ~211 merge_to_canonical rows whose
   * canonical_tid was blank at CSV generation time (they target terms from
   * terms_to_create.csv that didn't exist yet when the CSV was produced).
   *
   * Returns an empty array if stage 1 --commit has not been run yet, which
   * causes the pre-flight check in stageContent/stageFollowers to throw.
   *
   * @return array<string, int> Keyed by canonical term name, value is the tid.
   */
  private function buildCreatedTermMap(): array {
    $rows = $this->db->select(self::LOG_TABLE, 'l')
      ->fields('l', ['name', 'target_tid'])
      ->condition('l.stage', 'create-terms')
      ->condition('l.action', ['term_created', 'term_exists'], 'IN')
      ->condition('l.is_dry_run', 0)
      ->isNotNull('l.target_tid')
      ->execute()
      ->fetchAllKeyed();
    return array_map('intval', $rows);
  }

  /**
   * Snapshots node ref and follower counts for all canonical terms.
   *
   * Canonical tids are: the tid of every already_canonical row, plus every
   * distinct canonical_tid from merge_to_canonical rows.
   */
  private function snapshotCanonicalCounts(array $rows, array $createdTermMap): array {
    $canonicalTids = [];
    foreach ($rows as $row) {
      if ($row['final_disposition'] === 'already_canonical') {
        $tid = (int) $row['tid'];
        if (!isset($canonicalTids[$tid])) {
          $canonicalTids[$tid] = $row['name'];
        }
      }
      elseif ($row['final_disposition'] === 'merge_to_canonical') {
        $tid = $this->resolveCanonicalTidForRow($row, $createdTermMap);
        if ($tid && !isset($canonicalTids[$tid])) {
          $canonicalTids[$tid] = $row['canonical_term'];
        }
      }
    }

    $snapshot = [];
    foreach ($canonicalTids as $tid => $name) {
      $nodeRefs = (int) $this->db->select('node__field_issues', 'f')
        ->condition('f.field_issues_target_id', $tid)
        ->countQuery()->execute()->fetchField();
      $followers = (int) $this->db->select('flagging', 'f')
        ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('f.entity_type', 'taxonomy_term')
        ->condition('f.entity_id', (string) $tid)
        ->countQuery()->execute()->fetchField();
      $snapshot[$tid] = [
        'name'      => $name,
        'nodes'     => $nodeRefs,
        'followers' => $followers,
      ];
    }

    return $snapshot;
  }

  /**
   * Outputs a before/after verification table for all canonical terms.
   *
   * Required after every --commit run. Shows exactly where nodes and followers
   * landed so discrepancies are visible immediately rather than found by chance.
   */
  private function verificationReport(array $rows, array $beforeCounts, string $stage, array $createdTermMap): void {
    $this->io()->section("Verification Report — after $stage");

    $afterCounts = $this->snapshotCanonicalCounts($rows, $createdTermMap);
    $tableRows = [];

    foreach ($afterCounts as $tid => $after) {
      $before = $beforeCounts[$tid] ?? ['nodes' => 0, 'followers' => 0];
      $nodesDelta = $after['nodes'] - $before['nodes'];
      $followersDelta = $after['followers'] - $before['followers'];
      $tableRows[] = [
        $after['name'],
        $tid,
        number_format($before['nodes']),
        number_format($after['nodes']),
        ($nodesDelta >= 0 ? '+' : '') . number_format($nodesDelta),
        number_format($before['followers']),
        number_format($after['followers']),
        ($followersDelta >= 0 ? '+' : '') . number_format($followersDelta),
      ];
    }

    usort($tableRows, fn($a, $b) => (int) str_replace(',', '', $b[3]) - (int) str_replace(',', '', $a[3]));

    $this->io()->table(
      ['Term', 'TID', 'Nodes before', 'Nodes after', 'Δ nodes', 'Follows before', 'Follows after', 'Δ follows'],
      $tableRows
    );
  }

  /**
   * Syncs the flag_counts table after direct flagging table writes.
   *
   * The Flag module maintains flag_counts as a cached aggregate. Our direct
   * INSERT/DELETE on the flagging table bypasses the Flag API, so flag_counts
   * must be updated explicitly after every followers --commit run.
   *
   * For merge_to_canonical canonical targets: recalculates the live count
   * from the flagging table and upserts into flag_counts.
   * For source tids (now 0 flaggings): deletes their flag_counts rows.
   */
  /**
   * Syncs the flag_counts table after direct flagging table writes.
   *
   * Called from stageFollowers --commit. The Flag module maintains flag_counts
   * as a cached aggregate written through its API; our direct INSERT/DELETE on
   * the flagging table bypasses that, so we must update the cache ourselves.
   *
   * merge_to_canonical source tids: their flaggings have been moved or dropped,
   * so their flag_counts rows are deleted.
   * Canonical target tids: count is recalculated live from the flagging table
   * and upserted into flag_counts.
   *
   * delete-disposition tids are NOT processed here — their flaggings are still
   * intact at this stage. stageDelete handles their flag_counts cleanup inline
   * when it drops those flaggings.
   *
   * @param int[] $extraTidsToRemove Additional tids whose flag_counts rows
   *   should be removed (used by stageDelete for delete-disposition terms).
   */
  private function syncFlagCounts(array $rows, array $createdTermMap, array $extraTidsToRemove = []): void {
    $requestTime = \Drupal::time()->getRequestTime();

    $sourceTids = [];
    $canonicalTids = [];
    foreach ($rows as $row) {
      if ($row['final_disposition'] !== 'merge_to_canonical') {
        continue;
      }
      $sourceTids[] = (int) $row['tid'];
      $cTid = $this->resolveCanonicalTidForRow($row, $createdTermMap);
      if ($cTid) {
        $canonicalTids[$cTid] = TRUE;
      }
    }

    // Remove flag_counts rows for all tids whose flaggings have been removed.
    $tidsToRemove = array_merge($sourceTids, $extraTidsToRemove);
    foreach (array_chunk($tidsToRemove, 500) as $chunk) {
      $this->db->delete('flag_counts')
        ->condition('flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('entity_type', 'taxonomy_term')
        ->condition('entity_id', array_map('strval', $chunk), 'IN')
        ->execute();
    }

    // Recalculate and upsert the live count for each canonical tid.
    foreach (array_keys($canonicalTids) as $cTid) {
      $count = (int) $this->db->select('flagging', 'f')
        ->condition('f.flag_id', self::FOLLOW_ISSUE_FLAG)
        ->condition('f.entity_type', 'taxonomy_term')
        ->condition('f.entity_id', (string) $cTid)
        ->countQuery()->execute()->fetchField();
      $this->db->merge('flag_counts')
        ->keys([
          'flag_id'   => self::FOLLOW_ISSUE_FLAG,
          'entity_id' => (string) $cTid,
        ])
        ->fields([
          'entity_type'  => 'taxonomy_term',
          'count'        => $count,
          'last_updated' => $requestTime,
        ])
        ->execute();
    }
  }

  /**
   * Parses a CSV file and returns an array of associative rows.
   */
  private function parseCsv(string $path): array {
    $rows = [];
    $handle = fopen($path, 'r');
    if (!$handle) {
      throw new \RuntimeException("Cannot open CSV: $path");
    }
    $headers = fgetcsv($handle);
    // Normalize 'otid' → 'tid' so scoped test CSVs work alongside the full CSV.
    $headers = array_map(fn($h) => $h === 'otid' ? 'tid' : $h, $headers);
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (count($data) === count($headers)) {
        $rows[] = array_combine($headers, $data);
      }
    }
    fclose($handle);
    return $rows;
  }

  /**
   * Returns TRUE if a tid exists in taxonomy_term_field_data.
   */
  private function tidExistsInDb(int $tid): bool {
    return (bool) $this->db->select('taxonomy_term_field_data', 't')
      ->condition('t.tid', $tid)
      ->countQuery()->execute()->fetchField();
  }

  /**
   * Throws if the migration log table does not exist.
   */
  private function ensureLogTable(): void {
    if (!$this->db->schema()->tableExists(self::LOG_TABLE)) {
      throw new \RuntimeException(
        'Migration log table does not exist. Enable the module and run: drush updb'
      );
    }
  }

  /**
   * Writes one entry to the migration log table.
   */
  private function writeLog(
    string $stage,
    int $tid,
    string $name,
    string $action,
    ?int $targetTid,
    ?string $targetName,
    ?string $detail,
    bool $isDryRun,
  ): void {
    $this->db->insert(self::LOG_TABLE)
      ->fields([
        'stage'       => $stage,
        'tid'         => $tid,
        'name'        => substr($name, 0, 255),
        'action'      => $action,
        'target_tid'  => $targetTid,
        'target_name' => $targetName ? substr($targetName, 0, 255) : NULL,
        'detail'      => $detail ? substr($detail, 0, 512) : NULL,
        'is_dry_run'  => (int) $isDryRun,
        'created'     => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }

}
