<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command to set field_related_issue on global_issues terms from CSV.
 *
 * Pass 2 of the canonical terms setup: pass 1
 * (CanonicalTermsImportCommands) creates the terms themselves; this reads
 * back a CSV of term name -> related term names and writes
 * field_related_issue, resolving every name against whatever tids the
 * current environment actually assigned. Kept as a separate command/CSV
 * rather than folded into pass 1, since it depends on terms that only
 * exist after pass 1 has run.
 *
 * Deliberately name-based, not tid-based: taxonomy term ids are
 * auto-increment values specific to each database, so a CSV built with
 * one environment's tids (e.g. local) will not line up with another's
 * (e.g. a fresh multidev) - the same term name can land on a different
 * tid depending on how many other terms already existed in that
 * database. Resolving by name is portable across every environment.
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
   * Expects columns: tid, field_related_issue, name. The tid column is
   * informational only (whatever tid the term happened to have on the
   * environment the CSV was generated on) and is not used for lookups -
   * both the row's own term and every related term are resolved by name
   * against the current environment's global_issues vocabulary, since
   * tids are not portable across databases. Runs as a dry-run by
   * default; pass --commit to write.
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
    $nameToTid = $this->buildNameToTidMap($storage);

    $counts = ['updated' => 0, 'name_not_found' => 0, 'invalid_related_name' => 0];

    foreach ($rows as $row) {
      $csv_name = trim($row['name'] ?? '');
      if ($csv_name === '') {
        continue;
      }

      $tid = $nameToTid[strtolower($csv_name)] ?? NULL;
      if (!$tid) {
        $counts['name_not_found']++;
        $this->io()->text("  SKIP (\"{$csv_name}\" not found in " . self::VOCABULARY . ')');
        continue;
      }
      $term = $storage->load($tid);

      $related_names = array_values(array_filter(array_map('trim', explode(',', $row['field_related_issue'] ?? ''))));
      $valid_tids = [];
      foreach ($related_names as $related_name) {
        $related_tid = $nameToTid[strtolower($related_name)] ?? NULL;
        if ($related_tid) {
          $valid_tids[] = $related_tid;
        }
        else {
          $counts['invalid_related_name']++;
          $this->io()->text("  WARNING (\"{$csv_name}\"): related term \"{$related_name}\" not found in " . self::VOCABULARY . ', skipping that reference');
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
        ['Skipped: name not found in vocabulary', number_format($counts['name_not_found'])],
        ['Related name references dropped (invalid)', number_format($counts['invalid_related_name'])],
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
   * Builds a lowercased term name => tid map for the global_issues vocabulary.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The taxonomy term storage.
   *
   * @return array<string, int>
   *   Lowercased term name keyed to tid.
   */
  protected function buildNameToTidMap(EntityStorageInterface $storage): array {
    $ids = $storage->getQuery()
      ->condition('vid', self::VOCABULARY)
      ->accessCheck(FALSE)
      ->execute();

    $map = [];
    foreach ($storage->loadMultiple($ids) as $term) {
      $map[strtolower($term->getName())] = (int) $term->id();
    }
    return $map;
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
