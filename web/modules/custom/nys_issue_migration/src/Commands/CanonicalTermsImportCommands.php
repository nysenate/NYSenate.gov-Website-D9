<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;

/**
 * Drush command to import canonical global_issues terms from a CSV.
 *
 * Reusable across environments (local, test, live): the CSV ships with the
 * codebase under migration-data/issue-tags/ at the project root - not
 * sites/default/files/, which is gitignored and wouldn't travel with a
 * code deploy - and this command is the repeatable substitute for a Feeds
 * importer, matching terms by name so re-running it (with an updated CSV)
 * updates existing terms instead of duplicating them.
 *
 * Usage:
 *   drush global-issues-import-terms                  # dry-run
 *   drush global-issues-import-terms --commit          # write
 */
class CanonicalTermsImportCommands extends DrushCommands {

  const VOCABULARY = 'global_issues';
  const DEFAULT_CSV = 'migration-data/issue-tags/pass1-canonical-terms-create.csv';

  /**
   * Constructs the CanonicalTermsImportCommands object.
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
   * Import canonical global_issues terms from a CSV.
   *
   * Expects columns: name, field_keywords, description. field_keywords is
   * a comma-separated list in the CSV, split into the field's separate
   * string values on import. Runs as a dry-run by default; pass --commit
   * to write. Terms are matched by name - if a term with that name already
   * exists in the global_issues vocabulary, it's updated in place rather
   * than duplicated.
   *
   * @command global-issues-import-terms
   * @option csv Path to the CSV, relative to the project root or absolute. Defaults to migration-data/issue-tags/pass1-canonical-terms-create.csv.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush global-issues-import-terms
   *   Dry-run: report which terms would be created or updated.
   * @usage drush global-issues-import-terms --commit
   *   Create/update canonical global_issues terms.
   * @usage drush global-issues-import-terms --csv=/path/to/other.csv --commit
   *   Import from a specific CSV path.
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

    $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

    foreach ($rows as $row) {
      $name = trim($row['name'] ?? '');
      if ($name === '') {
        $counts['skipped']++;
        continue;
      }

      $keywords = array_values(array_filter(array_map('trim', explode(',', $row['field_keywords'] ?? ''))));
      $description = trim($row['description'] ?? '');

      $existing = $storage->loadByProperties(['vid' => self::VOCABULARY, 'name' => $name]);
      $term = $existing ? reset($existing) : Term::create(['vid' => self::VOCABULARY, 'name' => $name]);
      $is_new = $term->isNew();

      $term->set('field_keywords', $keywords);
      $term->set('description', ['value' => $description, 'format' => 'basic_html']);

      if ($commit) {
        $term->save();
      }

      if ($is_new) {
        $counts['created']++;
        $this->io()->text(sprintf('  %s (tid %s): %s', $commit ? 'CREATED' : 'WOULD CREATE', $commit ? $term->id() : 'N/A', $name));
      }
      else {
        $counts['updated']++;
        $this->io()->text(sprintf('  %s (tid %s): %s', $commit ? 'UPDATED' : 'WOULD UPDATE', $term->id(), $name));
      }
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Terms created', number_format($counts['created'])],
        ['Existing terms updated', number_format($counts['updated'])],
        ['Rows skipped (no name)', number_format($counts['skipped'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
    else {
      $this->io()->success(sprintf('Created %d, updated %d canonical terms.', $counts['created'], $counts['updated']));
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
