<?php

declare(strict_types=1);

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_bills\BillsHelper;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\TermInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command to create senator "Featured Legislation" paragraphs from CSV.
 *
 * Source rows come from press releases that list bills and a sponsor
 * quote - each row becomes one issue_featured_legislation paragraph
 * (field_featured_bill + field_featured_quote), appended to the
 * sponsoring senator's field_featured_legislation. Bills are resolved by
 * title (session + print number), senators by exact taxonomy term name -
 * both are looked up fresh against the current environment, not baked
 * into the CSV as ids, for the same portability reason field_related_issue
 * import was fixed to resolve by name (see
 * CanonicalTermsRelatedImportCommands).
 *
 * Usage:
 *   drush featured-legislation-import                  # dry-run
 *   drush featured-legislation-import --commit          # write
 */
class FeaturedLegislationImportCommands extends DrushCommands {

  const PARAGRAPH_BUNDLE = 'issue_featured_legislation';
  const FEATURED_FIELD = 'field_featured_legislation';
  const CARDINALITY = 5;
  const DEFAULT_CSV = 'migration-data/issue-tags/press_release_bills_sample.csv';

  /**
   * Loaded senator terms, keyed by CSV sponsor name - cached for the run.
   *
   * @var array<string, \Drupal\taxonomy\TermInterface|null>
   */
  protected array $senatorCache = [];

  /**
   * Constructs the FeaturedLegislationImportCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\nys_bills\BillsHelper $billsHelper
   *   The bills helper, used to resolve a bill's title.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BillsHelper $billsHelper,
  ) {
    parent::__construct();
  }

  /**
   * Creates featured-legislation paragraphs from a bill/sponsor/quote CSV.
   *
   * Expects columns: bill_number, sponsor, quote, source_url, and an
   * optional global_issues column (semicolon-separated canonical
   * global_issues term names). source_url is informational only, not
   * stored. An optional placeholder column (any truthy value) marks a row
   * as not ready to publish - e.g. a real sponsor quote wasn't found yet -
   * and is always skipped, regardless of how well the rest of the row
   * resolves. bill_number is matched against the bill's title (e.g.
   * "S.363A" -> "2025-S363A"); sponsor is matched by exact senator
   * taxonomy term name; global_issues names are matched by exact term
   * name. Skips a row if the bill or sponsor can't be resolved, if that
   * senator already features the bill, or if field_featured_legislation
   * is already at its 5-item cardinality limit. When global_issues is
   * supplied, valid names are merged into the bill's existing
   * field_global_issues (not overwritten, and never duplicated) -
   * unresolvable names are dropped with a warning rather than failing the
   * row. Runs as a dry-run by default; pass --commit to write.
   *
   * @command featured-legislation-import
   * @option csv Path to the CSV, relative to the project root or absolute. Defaults to migration-data/issue-tags/press_release_bills_sample.csv.
   * @option session The bill session year. Defaults to 2025.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush featured-legislation-import
   *   Dry-run: report which paragraphs would be created.
   * @usage drush featured-legislation-import --commit
   *   Create the featured-legislation paragraphs.
   */
  public function import(
    array $options = [
      'csv' => NULL,
      'session' => 2025,
      'commit' => FALSE,
    ],
  ): void {
    $commit = (bool) $options['commit'];
    $session = (int) $options['session'];
    $csv_path = $options['csv'] ?: self::DEFAULT_CSV;
    if (!str_starts_with($csv_path, '/')) {
      $csv_path = dirname(DRUPAL_ROOT) . '/' . $csv_path;
    }

    if (!file_exists($csv_path)) {
      $this->io()->error("CSV not found: {$csv_path}");
      return;
    }

    $rows = $this->parseCsv($csv_path);
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $issueNameToTid = $this->buildIssueNameToTidMap($termStorage);

    $counts = [
      'created' => 0,
      'placeholder' => 0,
      'bill_not_found' => 0,
      'sponsor_not_found' => 0,
      'already_featured' => 0,
      'cardinality_full' => 0,
      'issues_tagged' => 0,
      'issue_name_invalid' => 0,
    ];

    foreach ($rows as $row) {
      $bill_number = strtoupper(str_replace('.', '', trim($row['bill_number'] ?? '')));
      $sponsor = trim($row['sponsor'] ?? '');
      $quote = trim($row['quote'] ?? '');
      if ($bill_number === '' || $sponsor === '') {
        continue;
      }

      // A "placeholder" column marks rows where the source data is
      // incomplete (e.g. no real sponsor quote found yet) - never publish
      // those, regardless of how well everything else in the row resolves.
      if (filter_var($row['placeholder'] ?? FALSE, FILTER_VALIDATE_BOOLEAN)) {
        $counts['placeholder']++;
        $this->io()->text("  SKIP ($sponsor): row marked placeholder, not ready to publish");
        continue;
      }

      $title = "$session-$bill_number";
      $bill = $this->billsHelper->loadBillByTitle($title);
      if (!$bill) {
        $counts['bill_not_found']++;
        $this->io()->text("  SKIP ($sponsor): bill \"$title\" not found");
        continue;
      }

      $senator = $this->getSenatorTerm($sponsor, $termStorage);
      if (!$senator) {
        $counts['sponsor_not_found']++;
        $this->io()->text("  SKIP: senator \"$sponsor\" not found");
        continue;
      }

      // global_issues tagging is a property of the bill, not of whether a
      // new featured-legislation paragraph gets created this run - applies
      // even on a row skipped below as already-featured/cardinality-full.
      $this->applyGlobalIssues($bill, trim($row['global_issues'] ?? ''), $issueNameToTid, $commit, $counts);

      $billNid = (int) $bill->id();
      if (in_array($billNid, $this->getExistingFeaturedBillNids($senator), TRUE)) {
        $counts['already_featured']++;
        $this->io()->text("  SKIP ($sponsor): already features \"$title\"");
        continue;
      }

      if ($senator->get(self::FEATURED_FIELD)->count() >= self::CARDINALITY) {
        $counts['cardinality_full']++;
        $this->io()->text("  SKIP ($sponsor): \"$title\" - " . self::FEATURED_FIELD . ' already has ' . self::CARDINALITY . ' items');
        continue;
      }

      // Append (in memory) regardless of --commit, so the field's count()
      // stays accurate for the cardinality check above across multiple
      // rows targeting the same senator in this run - otherwise a dry-run
      // undercounts how many rows would actually hit the cardinality cap,
      // since nothing here gets persisted until $commit is true anyway.
      $paragraph = Paragraph::create([
        'type' => self::PARAGRAPH_BUNDLE,
        'field_featured_bill' => ['target_id' => $billNid],
        'field_featured_quote' => $quote,
      ]);
      $senator->get(self::FEATURED_FIELD)->appendItem($paragraph);
      if ($commit) {
        $paragraph->save();
        $senator->save();
      }

      $counts['created']++;
      $this->io()->text(sprintf('  %s (%s): %s', $commit ? 'CREATED' : 'WOULD CREATE', $sponsor, $title));
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Paragraphs ' . ($commit ? 'created' : 'that would be created'), number_format($counts['created'])],
        ['Skipped: placeholder row', number_format($counts['placeholder'])],
        ['Skipped: bill not found', number_format($counts['bill_not_found'])],
        ['Skipped: sponsor not found', number_format($counts['sponsor_not_found'])],
        ['Skipped: already featured', number_format($counts['already_featured'])],
        ['Skipped: cardinality limit reached', number_format($counts['cardinality_full'])],
        [
          'Bills ' . ($commit ? 'tagged' : 'that would be tagged') . ' with global_issues',
          number_format($counts['issues_tagged']),
        ],
        ['global_issues names dropped (invalid)', number_format($counts['issue_name_invalid'])],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
    else {
      $this->io()->success(sprintf('Created %d featured-legislation paragraphs.', $counts['created']));
    }
  }

  /**
   * Merges CSV-supplied global_issues names onto a bill, if any were given.
   *
   * Adds to the bill's existing field_global_issues rather than replacing
   * it, and never adds a tid the bill already has. Silently does nothing
   * if $csvValue is empty - global_issues is an optional CSV column, most
   * bills get tagged separately via the bill classifier instead.
   *
   * @param \Drupal\node\NodeInterface $bill
   *   The bill node.
   * @param string $csvValue
   *   The row's raw global_issues cell: semicolon-separated term names.
   * @param array<string, int> $issueNameToTid
   *   Lowercased global_issues term name => tid.
   * @param bool $commit
   *   Whether to actually save the bill.
   * @param array $counts
   *   The running counts array, updated by reference.
   */
  protected function applyGlobalIssues(NodeInterface $bill, string $csvValue, array $issueNameToTid, bool $commit, array &$counts): void {
    if ($csvValue === '') {
      return;
    }

    $names = array_values(array_filter(array_map('trim', explode(';', $csvValue))));
    $existingTids = array_map('intval', array_column($bill->get('field_global_issues')->getValue(), 'target_id'));

    $newTids = [];
    foreach ($names as $name) {
      $tid = $issueNameToTid[strtolower($name)] ?? NULL;
      if (!$tid) {
        $counts['issue_name_invalid']++;
        $this->io()->text("    WARNING: global_issues name \"$name\" not found, skipping");
        continue;
      }
      if (!in_array($tid, $existingTids, TRUE) && !in_array($tid, $newTids, TRUE)) {
        $newTids[] = $tid;
      }
    }

    if (!$newTids) {
      return;
    }

    $counts['issues_tagged']++;
    if ($commit) {
      $bill->set('field_global_issues', array_map(
        fn (int $id) => ['target_id' => $id],
        array_merge($existingTids, $newTids)
      ));
      $bill->save();
    }
  }

  /**
   * Loads (and caches) a senator taxonomy term by exact name.
   *
   * @param string $name
   *   The sponsor name, as it appears in the CSV.
   * @param \Drupal\Core\Entity\EntityStorageInterface $termStorage
   *   The taxonomy term storage.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The matching senator term, or NULL if none matches.
   */
  protected function getSenatorTerm(string $name, EntityStorageInterface $termStorage): ?TermInterface {
    if (!array_key_exists($name, $this->senatorCache)) {
      $terms = $termStorage->loadByProperties(['vid' => 'senator', 'name' => $name]);
      $this->senatorCache[$name] = $terms ? reset($terms) : NULL;
    }
    return $this->senatorCache[$name];
  }

  /**
   * Builds a lowercased term name => tid map for the global_issues vocabulary.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $termStorage
   *   The taxonomy term storage.
   *
   * @return array<string, int>
   *   Lowercased term name keyed to tid.
   */
  protected function buildIssueNameToTidMap(EntityStorageInterface $termStorage): array {
    $ids = $termStorage->getQuery()
      ->condition('vid', 'global_issues')
      ->accessCheck(FALSE)
      ->execute();

    $map = [];
    foreach ($termStorage->loadMultiple($ids) as $term) {
      $map[strtolower($term->getName())] = (int) $term->id();
    }
    return $map;
  }

  /**
   * Gets the bill node ids a senator's existing featured paragraphs reference.
   *
   * @param \Drupal\taxonomy\TermInterface $senator
   *   The senator term.
   *
   * @return int[]
   *   Bill node ids already referenced via field_featured_legislation.
   */
  protected function getExistingFeaturedBillNids(TermInterface $senator): array {
    $nids = [];
    foreach ($senator->get(self::FEATURED_FIELD) as $item) {
      $paragraph = $item->entity;
      if ($paragraph && $paragraph->hasField('field_featured_bill') && !$paragraph->get('field_featured_bill')->isEmpty()) {
        $nids[] = (int) $paragraph->get('field_featured_bill')->target_id;
      }
    }
    return $nids;
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
