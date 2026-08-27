<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command to set field_related_issue on global_issues terms from CSV.
 *
 * Pass 2 of the canonical terms setup: pass 1
 * (CanonicalTermsImportCommands) creates the terms themselves; this reads
 * back a CSV of tid -> related tids (built externally against the
 * name/tid export) and writes field_related_issue. Kept as a separate
 * command/CSV rather than folded into pass 1, since it depends on tids
 * that only exist after pass 1 has run.
 *
 * Usage:
 *   drush global-issues-import-related                  # dry-run
 *   drush global-issues-import-related --commit          # write
 */
class CanonicalTermsRelatedImportCommands extends DrushCommands {

  const VOCABULARY = 'global_issues';
  const DEFAULT_CSV = 'migration-data/issue-tags/pass2-field-related-issue-update.csv';

  /**
   * Constructs the CanonicalTermsRelatedImportCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Import field_related_issue values for global_issues terms from a CSV.
   *
   * Expects columns: tid, field_related_issue, name. field_related_issue
   * is a comma-separated list of related term tids. The name column is
   * used only to sanity-check against the term's current name - a
   * mismatch usually means the tid no longer refers to the term the CSV
   * was built against (e.g. terms were deleted and re-created since the
   * name/tid export), and the row is skipped rather than guessed at.
   * Runs as a dry-run by default; pass --commit to write.
   *
   * @command global-issues-import-related
   * @option csv Path to the CSV, relative to the project root or absolute. Defaults to migration-data/issue-tags/pass2-field-related-issue-update.csv.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush global-issues-import-related
   *   Dry-run: report which terms' field_related_issue would be updated.
   * @usage drush global-issues-import-related --commit
   *   Write field_related_issue on the canonical global_issues terms.
   */
  public function import(
    array $options = [
      'csv' => NULL,
      'commit' => FALSE,
    ],
  ): void {
    $commit = (bool) $options['commit'];
    $csv_path = $options['csv'] ?: self::DEFAULT_CSV;
    if (!str_starts_with($csv_path, '/')) {
      $csv_path = dirname(DRUPAL_ROOT) . '/' . $csv_path;
    }

    if (!file_exists($csv_path)) {
      $this->io()->error("CSV not found: {$csv_path}");
      return;
    }

    $rows = $this->parseCsv($csv_path);
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $counts = ['updated' => 0, 'name_mismatch' => 0, 'tid_not_found' => 0, 'invalid_related_tid' => 0];

    foreach ($rows as $row) {
      $tid = trim($row['tid'] ?? '');
      $csv_name = trim($row['name'] ?? '');
      if ($tid === '') {
        continue;
      }

      $term = $storage->load($tid);
      if (!$term || $term->bundle() !== self::VOCABULARY) {
        $counts['tid_not_found']++;
        $this->io()->text("  SKIP (tid {$tid} not found in " . self::VOCABULARY . "): {$csv_name}");
        continue;
      }

      if ($term->getName() !== $csv_name) {
        $counts['name_mismatch']++;
        $this->io()->text("  SKIP (tid {$tid} name mismatch - CSV says \"{$csv_name}\", term is \"{$term->getName()}\")");
        continue;
      }

      $related_tids = array_values(array_filter(array_map('trim', explode(',', $row['field_related_issue'] ?? ''))));
      $valid_tids = [];
      foreach ($related_tids as $related_tid) {
        $related_term = $storage->load($related_tid);
        if ($related_term && $related_term->bundle() === self::VOCABULARY) {
          $valid_tids[] = $related_tid;
        }
        else {
          $counts['invalid_related_tid']++;
          $this->io()->text("  WARNING (tid {$tid} \"{$csv_name}\"): related tid {$related_tid} not found in " . self::VOCABULARY . ', skipping that reference');
        }
      }

      $term->set('field_related_issue', array_map(fn($id) => ['target_id' => $id], $valid_tids));
      if ($commit) {
        $term->save();
      }

      $counts['updated']++;
      $this->io()->text(sprintf('  %s (tid %s): %s -> [%s]', $commit ? 'UPDATED' : 'WOULD UPDATE', $tid, $csv_name, implode(', ', $valid_tids)));
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Terms updated', number_format($counts['updated'])],
        ['Skipped: tid not found', number_format($counts['tid_not_found'])],
        ['Skipped: name mismatch', number_format($counts['name_mismatch'])],
        ['Related tid references dropped (invalid)', number_format($counts['invalid_related_tid'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
    else {
      $this->io()->success(sprintf('Updated field_related_issue on %d canonical terms.', $counts['updated']));
    }
  }

  /**
   * Parses a CSV file into an array of associative rows keyed by header.
   *
   * @param string $path
   *   Absolute path to the CSV file.
   *
   * @return array
   *   Rows keyed by column header.
   */
  private function parseCsv(string $path): array {
    $rows = [];
    $handle = fopen($path, 'r');
    $headers = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (count($data) !== count($headers)) {
        continue;
      }
      $rows[] = array_combine($headers, $data);
    }
    fclose($handle);
    return $rows;
  }

}
