<?php

declare(strict_types=1);

namespace Drupal\nys_bill_classifier\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_bill_classifier\Service\BillClassifierService;
use Drupal\nys_bills\BillsHelper;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for the bill issue classifier.
 *
 * Test-sample: report-only, classifies a small sample for manual review,
 * never writes. classify-batch: classifies and writes field_global_issues
 * for a committee-diverse batch of bills - see bill-classifier-prompt.md
 * at the repo root for the classification design. export-tags/
 * import-tags: move a completed batch's results from one environment to
 * another (e.g. local, where classify-batch ran without incident, to a
 * remote environment prone to dropping long-lived DB connections) without
 * re-running classification - see their own docblocks for why that's
 * title/name-based rather than nid/tid-based.
 */
class BillClassifierCommands extends DrushCommands {

  const DEFAULT_EXPORT_CSV = 'migration-data/issue-tags/bill-global-issues-export.csv';

  /**
   * Constructs the BillClassifierCommands object.
   *
   * @param \Drupal\nys_bill_classifier\Service\BillClassifierService $classifier
   *   The bill classifier service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\nys_bills\BillsHelper $billsHelper
   *   The bills helper, used to format a bill's print number.
   */
  public function __construct(
    protected BillClassifierService $classifier,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $db,
    protected BillsHelper $billsHelper,
  ) {
    parent::__construct();
  }

  /**
   * Classifies a small sample of bills and reports the results.
   *
   * Report-only: nothing is written to field_global_issues. The sample
   * deliberately mixes bills already manually tagged (to eyeball against
   * the classifier), bills with a sponsor position quote, and a random
   * cross-section of the session, per the Phase 1 testing protocol.
   *
   * @command bill-classifier:test-sample
   * @option limit Total number of bills to sample.
   * @option session The bill session year to sample from.
   * @option nids Comma-separated bill node ids to classify, bypassing
   *   sample selection entirely - lets a prior sample be re-run unchanged.
   * @option export Path to write a full, untruncated CSV of the results
   *   (nid, bill number, title, summary, existing tags, classified issues,
   *   primary issue, confidence, rationale). The terminal table always
   *   truncates long text for readability; this does not.
   * @usage drush bill-classifier:test-sample
   *   Classify 50 sample bills from the 2025 session and print a report.
   * @usage drush bill-classifier:test-sample --limit=10
   *   Classify a smaller sample.
   * @usage drush bill-classifier:test-sample --nids=123,456 --export=/tmp/review.csv
   *   Re-classify a specific set of bills and write the full results to CSV.
   */
  public function testSample(
    array $options = [
      'limit' => 50,
      'session' => 2025,
      'nids' => NULL,
      'export' => NULL,
    ],
  ): void {
    $session = (int) $options['session'];

    if (!empty($options['nids'])) {
      $nids = array_map('intval', explode(',', $options['nids']));
    }
    else {
      $nids = $this->buildSampleNids((int) $options['limit'], $session);
    }
    if (!$nids) {
      $this->io()->warning("No bills found for session $session.");
      return;
    }

    $bills = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $existingTags = $this->getExistingTagNames(array_keys($bills));

    $rows = [];
    $exportRows = [];
    foreach ($bills as $nid => $bill) {
      $billNumber = $this->billsHelper->formatTitle($bill);
      $name = $bill->hasField('field_ol_name') ? (string) $bill->get('field_ol_name')->value : '';
      $summary = $bill->hasField('field_ol_summary') ? (string) $bill->get('field_ol_summary')->value : '';

      try {
        $result = $this->classifier->classifyBill($bill);
      }
      catch (\RuntimeException $e) {
        $rows[] = [$nid, $billNumber, $name, $existingTags[$nid] ?? '', 'ERROR', '', '', $e->getMessage()];
        $exportRows[] = [
          $nid, $billNumber, $name, $summary,
          $existingTags[$nid] ?? '', 'ERROR', '', '', $e->getMessage(),
        ];
        continue;
      }

      $rows[] = [
        $nid,
        $billNumber,
        mb_strimwidth($name, 0, 50, '…'),
        $existingTags[$nid] ?? '',
        implode(', ', $result['issues']),
        $result['primary_issue'] ?? '',
        $result['confidence'],
        mb_strimwidth($result['rationale'], 0, 80, '…'),
      ];
      $exportRows[] = [
        $nid,
        $billNumber,
        $name,
        $summary,
        $existingTags[$nid] ?? '',
        implode(', ', $result['issues']),
        $result['primary_issue'] ?? '',
        $result['confidence'],
        $result['rationale'],
      ];
    }

    $this->io()->table(
      ['NID', 'Bill', 'Name', 'Existing tags', 'Classified issues', 'Primary', 'Confidence', 'Rationale'],
      $rows
    );

    if (!empty($options['export'])) {
      $this->writeExportCsv($options['export'], $exportRows);
      $this->io()->success('Wrote full results to ' . $options['export']);
    }
  }

  /**
   * Classifies and optionally writes a committee-diverse batch of bills.
   *
   * Intended for populating a dev/test site with real classifications
   * quickly, not as the eventual full-corpus backfill (that should use
   * OpenAI's Batch API once the prompt is considered final - this command
   * makes one live synchronous call per bill, so it's slow and relatively
   * more expensive at large scale). Selection is stratified by
   * field_ol_latest_status_committee as a cheap proxy for issue-topic
   * diversity - true issue diversity can't be known before classifying,
   * but committee is a reasonable stand-in and is known up front. Bills
   * already carrying a field_global_issues value are skipped entirely.
   *
   * @command bill-classifier:classify-batch
   * @option limit Number of bills to classify.
   * @option session The bill session year to sample from.
   * @option nids Comma-separated bill node ids to classify, bypassing
   *   diverse-batch selection entirely - e.g. bills that were just
   *   manually featured and need a global_issues tag now rather than
   *   waiting to land in a future random batch. Ignores the
   *   already-tagged skip, so it also works to re-classify specific bills.
   * @option commit Write results to field_global_issues. Without this
   *   flag, only reports what would happen.
   * @usage drush bill-classifier:classify-batch --limit=250
   *   Report what a 250-bill diverse batch would classify to.
   * @usage drush bill-classifier:classify-batch --limit=250 --commit
   *   Classify and write field_global_issues for 250 bills.
   * @usage drush bill-classifier:classify-batch --nids=123,456 --commit
   *   Classify and write field_global_issues for specific bills.
   */
  public function classifyBatch(
    array $options = [
      'limit' => 250,
      'session' => 2025,
      'nids' => NULL,
      'commit' => FALSE,
    ],
  ): void {
    $limit = (int) $options['limit'];
    $session = (int) $options['session'];
    $commit = (bool) $options['commit'];

    if (!empty($options['nids'])) {
      $nids = array_map('intval', explode(',', $options['nids']));
    }
    else {
      $nids = $this->buildDiverseNids($limit, $session);
    }
    if (!$nids) {
      $this->io()->warning("No untagged bills found for session $session.");
      return;
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $bills = $nodeStorage->loadMultiple($nids);

    $counts = ['processed' => 0, 'tagged' => 0, 'empty' => 0, 'errors' => 0];
    $byIssue = [];

    foreach ($bills as $nid => $bill) {
      $counts['processed']++;

      // Each OpenAI call can take a few seconds, and this loop may go
      // several bills between actual writes (empty-result bills touch
      // nothing) - on some hosts that's long enough for the DB connection
      // to sit idle past its wait_timeout and get dropped. A trivial
      // query here resets that idle clock every iteration regardless of
      // whether this particular bill ends up writing anything.
      $this->db->query('SELECT 1');

      try {
        $result = $this->classifier->classifyBill($bill);
      }
      catch (\RuntimeException $e) {
        $counts['errors']++;
        $this->io()->warning("Bill $nid: " . $e->getMessage());
        continue;
      }

      if (empty($result['issues'])) {
        $counts['empty']++;
        continue;
      }

      foreach ($result['issues'] as $issue) {
        $byIssue[$issue] = ($byIssue[$issue] ?? 0) + 1;
      }
      $counts['tagged']++;

      if ($commit) {
        $tids = $this->classifier->resolveIssueTids($result['issues']);
        if ($tids) {
          try {
            $this->writeGlobalIssues($bill, $tids);
            // Cleared immediately, not batched for the end of the run -
            // writeGlobalIssues() is raw SQL, so nothing else marks this
            // bill's cached entity stale. Batching this until after the
            // loop meant an abrupt kill (e.g. the connection-loss case
            // below, or the process just being terminated outright) could
            // leave every bill written so far with a stale cached copy -
            // happened for real: a 1000-bill run that got killed
            // externally left ~950 correctly-written bills invisible via
            // the entity API until a manual `drush cr`. resetCache() is
            // the one that actually matters for that - entity storage's
            // own persistent cache is keyed by id, not by the 'node:ID'
            // cache tag (confirmed against core: setPersistentCache()
            // tags its entries only with 'entity_field_info', not a
            // per-entity tag), so Cache::invalidateTags() alone doesn't
            // touch it. invalidateTags() is still worth calling alongside
            // it, for render/page/block caches that do use that tag.
            $nodeStorage->resetCache([$nid]);
            Cache::invalidateTags(['node:' . $nid]);
          }
          catch (\Exception $e) {
            // A dead DB connection won't recover mid-request - stop here
            // rather than let every remaining bill fail the same way (or
            // crash uncaught). Safe to just re-run the command: already-
            // tagged bills are excluded from selection automatically.
            $this->io()->error("Bill $nid: database write failed (" . $e->getMessage() . '). Stopping - already-tagged bills are skipped automatically, so it\'s safe to re-run this command to pick up where it left off.');
            $this->reportResults($counts, $byIssue, $commit);
            return;
          }
        }
      }

      if ($counts['processed'] % 25 === 0) {
        $this->io()->text("  Processed {$counts['processed']}/{$limit}...");
      }
    }

    $this->reportResults($counts, $byIssue, $commit);
  }

  /**
   * Prints the classify-batch summary tables.
   *
   * Shared by the normal end-of-run report and the early-stop path taken
   * if a database write fails partway through the batch.
   *
   * @param array $counts
   *   Keyed by 'processed', 'tagged', 'empty', 'errors'.
   * @param array $byIssue
   *   Issue name => count of bills classified with that issue.
   * @param bool $commit
   *   Whether this run was writing changes.
   */
  protected function reportResults(array $counts, array $byIssue, bool $commit): void {
    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Bills processed', $counts['processed']],
        ['Bills ' . ($commit ? 'tagged' : 'that would be tagged'), $counts['tagged']],
        ['Bills with no substantive issue', $counts['empty']],
        ['Errors', $counts['errors']],
      ]
    );

    if ($byIssue) {
      arsort($byIssue);
      $this->io()->table(
        ['Issue', 'Bill count'],
        array_map(static fn ($issue, $count) => [$issue, $count], array_keys($byIssue), $byIssue)
      );
    }

    if (!$commit) {
      $this->io()->note('Run with --commit to write these classifications to field_global_issues.');
    }
  }

  /**
   * Exports each tagged bill's field_global_issues as bill title => names.
   *
   * Deliberately title- and name-based, not nid/tid-based: node and term
   * ids are database-specific auto-increment values, not guaranteed to
   * match between environments seeded independently of each other (this
   * bit the field_related_issue migration earlier for exactly this
   * reason). Bill title and canonical term name are both content-derived
   * and portable - import-tags resolves them fresh on whatever
   * environment it runs against, rather than trusting this CSV's origin
   * environment's ids.
   *
   * @command bill-classifier:export-tags
   * @option csv Output path, relative to the project root or absolute.
   *   Defaults to migration-data/issue-tags/bill-global-issues-export.csv.
   * @option session The bill session year to export. Defaults to 2025.
   * @option nids Comma-separated bill node ids to limit the export to,
   *   instead of every tagged bill in the session.
   * @usage drush bill-classifier:export-tags
   *   Export every tagged 2025-session bill's global_issues.
   * @usage drush bill-classifier:export-tags --nids=123,456
   *   Export just specific bills.
   */
  public function exportTags(
    array $options = [
      'csv' => NULL,
      'session' => 2025,
      'nids' => NULL,
    ],
  ): void {
    $session = (int) $options['session'];
    $csv_path = $options['csv'] ?: self::DEFAULT_EXPORT_CSV;
    if (!str_starts_with($csv_path, '/')) {
      $csv_path = dirname(DRUPAL_ROOT) . '/' . $csv_path;
    }

    if (!empty($options['nids'])) {
      $nids = array_map('intval', explode(',', $options['nids']));
    }
    else {
      $query = $this->db->select('node__field_global_issues', 'fg')
        ->fields('fg', ['entity_id'])
        ->condition('fg.bundle', 'bill')
        ->distinct();
      $query->innerJoin('node__field_ol_session', 'fs', 'fs.entity_id = fg.entity_id AND fs.field_ol_session_value = :session', [':session' => $session]);
      $nids = array_map('intval', $query->execute()->fetchCol());
    }

    if (!$nids) {
      $this->io()->warning('No tagged bills found to export.');
      return;
    }

    $bills = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $tidToName = [];
    foreach ($this->classifier->getVocabulary() as $name => $info) {
      $tidToName[$info['tid']] = $name;
    }

    $rows = [];
    foreach ($bills as $bill) {
      $tids = array_map('intval', array_column($bill->get('field_global_issues')->getValue(), 'target_id'));
      $names = array_filter(array_map(fn (int $tid) => $tidToName[$tid] ?? NULL, $tids));
      if (!$names) {
        continue;
      }
      $rows[] = [(string) $bill->label(), implode('; ', $names)];
    }

    $handle = fopen($csv_path, 'w');
    fputcsv($handle, ['bill_title', 'global_issues']);
    foreach ($rows as $row) {
      fputcsv($handle, $row);
    }
    fclose($handle);

    $this->io()->success(sprintf('Exported %d bills to %s', count($rows), $csv_path));
  }

  /**
   * Imports a bill-title => global_issues CSV, merging into each bill.
   *
   * Resolves each bill by title (Bills::loadBillByTitle()) and each issue
   * name against this environment's own global_issues vocabulary - never
   * trusts ids from the CSV's origin environment. Adds to a bill's
   * existing field_global_issues rather than replacing it, and never adds
   * a tid the bill already has. Runs as a dry-run by default; pass
   * --commit to write.
   *
   * @command bill-classifier:import-tags
   * @option csv Path to the CSV, relative to the project root or absolute.
   *   Defaults to migration-data/issue-tags/bill-global-issues-export.csv.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush bill-classifier:import-tags
   *   Dry-run: report which bills would be tagged.
   * @usage drush bill-classifier:import-tags --commit
   *   Write field_global_issues from the CSV.
   */
  public function importTags(
    array $options = [
      'csv' => NULL,
      'commit' => FALSE,
    ],
  ): void {
    $commit = (bool) $options['commit'];
    $csv_path = $options['csv'] ?: self::DEFAULT_EXPORT_CSV;
    if (!str_starts_with($csv_path, '/')) {
      $csv_path = dirname(DRUPAL_ROOT) . '/' . $csv_path;
    }

    if (!file_exists($csv_path)) {
      $this->io()->error("CSV not found: {$csv_path}");
      return;
    }

    $rows = $this->parseTagsCsv($csv_path);
    $counts = [
      'processed' => 0,
      'bill_not_found' => 0,
      'issue_name_invalid' => 0,
      'tagged' => 0,
      'unchanged' => 0,
    ];

    foreach ($rows as $row) {
      $title = trim($row['bill_title'] ?? '');
      if ($title === '') {
        continue;
      }
      $counts['processed']++;

      $bill = $this->billsHelper->loadBillByTitle($title);
      if (!$bill) {
        $counts['bill_not_found']++;
        $this->io()->text("  SKIP: bill \"$title\" not found");
        continue;
      }

      $names = array_values(array_filter(array_map('trim', explode(';', $row['global_issues'] ?? ''))));
      $resolvedTids = $this->classifier->resolveIssueTids($names);
      if (count($resolvedTids) < count($names)) {
        $counts['issue_name_invalid'] += count($names) - count($resolvedTids);
        $this->io()->text("    WARNING (\"$title\"): " . (count($names) - count($resolvedTids)) . ' of ' . count($names) . ' issue name(s) not found');
      }

      $existingTids = array_map('intval', array_column($bill->get('field_global_issues')->getValue(), 'target_id'));
      $newTids = array_values(array_diff($resolvedTids, $existingTids));

      if (!$newTids) {
        $counts['unchanged']++;
        continue;
      }

      $counts['tagged']++;
      $this->io()->text(sprintf('  %s ("%s"): +[%s]', $commit ? 'TAGGED' : 'WOULD TAG', $title, implode(', ', $newTids)));
      if ($commit) {
        $bill->set('field_global_issues', array_map(
          fn (int $id) => ['target_id' => $id],
          array_merge($existingTids, $newTids)
        ));
        $bill->save();
      }
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Rows processed', $counts['processed']],
        ['Bills ' . ($commit ? 'tagged' : 'that would be tagged'), $counts['tagged']],
        ['Bills already up to date', $counts['unchanged']],
        ['Skipped: bill not found', $counts['bill_not_found']],
        ['Issue names dropped (invalid)', $counts['issue_name_invalid']],
      ]
    );

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
  }

  /**
   * Parses a bill-title/global_issues CSV into rows keyed by header.
   *
   * @param string $path
   *   Absolute path to the CSV file.
   *
   * @return array
   *   Rows keyed by column header.
   */
  private function parseTagsCsv(string $path): array {
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

  /**
   * Writes classified issue term ids to field_global_issues on a bill.
   *
   * Direct SQL, no new revision - this is a backend classification, not an
   * editorial change, matching the approach already used for the
   * field_issues -> field_global_issues backfill on non-bill content in
   * nys_issue_migration. Also mirrors published bills into taxonomy_index,
   * since Views' term_node_tid relationship reads from there rather than
   * the field tables directly, and core only maintains that index on
   * entity save.
   *
   * @param \Drupal\node\NodeInterface $bill
   *   The bill node.
   * @param int[] $tids
   *   The global_issues term ids to write.
   */
  protected function writeGlobalIssues(NodeInterface $bill, array $tids): void {
    $nid = (int) $bill->id();
    $vid = (int) $bill->getRevisionId();
    $bundle = $bill->bundle();
    $langcode = $bill->language()->getId();

    $delta = 0;
    foreach ($tids as $tid) {
      $this->db->insert('node__field_global_issues')
        ->fields([
          'bundle' => $bundle,
          'deleted' => 0,
          'entity_id' => $nid,
          'revision_id' => $vid,
          'langcode' => $langcode,
          'delta' => $delta,
          'field_global_issues_target_id' => $tid,
        ])
        ->execute();
      $this->db->insert('node_revision__field_global_issues')
        ->fields([
          'bundle' => $bundle,
          'deleted' => 0,
          'entity_id' => $nid,
          'revision_id' => $vid,
          'langcode' => $langcode,
          'delta' => $delta,
          'field_global_issues_target_id' => $tid,
        ])
        ->execute();
      $delta++;
    }

    if ($bill->isPublished()) {
      foreach ($tids as $tid) {
        $this->db->merge('taxonomy_index')
          ->keys(['nid' => $nid, 'tid' => $tid])
          ->fields([
            'status' => 1,
            'sticky' => (int) $bill->isSticky(),
            'created' => $bill->getCreatedTime(),
          ])
          ->execute();
      }
    }
  }

  /**
   * Builds a committee-diverse pool of untagged bill node ids.
   *
   * Buckets published, untagged session bills by their latest-status
   * committee (bills with no committee value share one bucket), shuffles
   * each bucket, then round-robins across buckets so no single committee
   * dominates the result - a proxy for issue-topic diversity, since the
   * real issue tags don't exist yet.
   *
   * @param int $limit
   *   Number of bill node ids to return.
   * @param int $session
   *   The bill session year.
   *
   * @return int[]
   *   Bill node ids.
   */
  protected function buildDiverseNids(int $limit, int $session): array {
    $alreadyTagged = $this->db->select('node__field_global_issues', 'fg')
      ->fields('fg', ['entity_id'])
      ->condition('fg.bundle', 'bill')
      ->execute()
      ->fetchCol();

    $query = $this->db->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', 'bill')
      ->condition('n.status', 1);
    $query->innerJoin('node__field_ol_session', 'fs', 'fs.entity_id = n.nid AND fs.field_ol_session_value = :session', [':session' => $session]);
    $query->leftJoin('node__field_ol_latest_status_committee', 'c', 'c.entity_id = n.nid');
    $query->addField('c', 'field_ol_latest_status_committee_value', 'committee');
    if ($alreadyTagged) {
      $query->condition('n.nid', $alreadyTagged, 'NOT IN');
    }

    $buckets = [];
    foreach ($query->execute() as $row) {
      $key = $row->committee ?: '_none';
      $buckets[$key][] = (int) $row->nid;
    }
    foreach ($buckets as &$bucket) {
      shuffle($bucket);
    }
    unset($bucket);

    $keys = array_keys($buckets);
    shuffle($keys);

    $nids = [];
    while (count($nids) < $limit && $keys) {
      foreach ($keys as $i => $key) {
        if (count($nids) >= $limit) {
          break;
        }
        if (empty($buckets[$key])) {
          unset($keys[$i]);
          continue;
        }
        $nids[] = array_pop($buckets[$key]);
      }
      $keys = array_values($keys);
    }

    return $nids;
  }

  /**
   * Writes the full (untruncated) sample results to a CSV file.
   *
   * @param string $path
   *   Destination file path.
   * @param array $rows
   *   Rows of [nid, bill_number, name, summary, existing_tags, issues,
   *   primary_issue, confidence, rationale].
   */
  protected function writeExportCsv(string $path, array $rows): void {
    $handle = fopen($path, 'w');
    fputcsv($handle, [
      'nid', 'bill_number', 'name', 'summary', 'existing_tags',
      'classified_issues', 'primary_issue', 'confidence', 'rationale',
    ]);
    foreach ($rows as $row) {
      fputcsv($handle, $row);
    }
    fclose($handle);
  }

  /**
   * Builds the sample of bill node ids to classify.
   *
   * Fills, in order: bills already manually tagged with field_global_issues
   * (up to 10), bills with a sponsor position quote (up to 10), then
   * random published bills from the session to fill the remaining slots.
   *
   * @param int $limit
   *   Total sample size.
   * @param int $session
   *   The bill session year.
   *
   * @return int[]
   *   Bill node ids.
   */
  protected function buildSampleNids(int $limit, int $session): array {
    $nids = [];

    $taggedBudget = min(10, $limit);
    if ($taggedBudget > 0) {
      $tagged = $this->db->select('node__field_global_issues', 'fg')
        ->fields('fg', ['entity_id'])
        ->condition('fg.bundle', 'bill')
        ->range(0, $taggedBudget)
        ->execute()
        ->fetchCol();
      $nids += array_combine($tagged, $tagged);
    }

    $quotedBudget = min(10, $limit - count($nids));
    if ($quotedBudget > 0) {
      $query = $this->db->select('paragraph__field_featured_bill', 'fb')
        ->fields('fb', ['field_featured_bill_target_id'])
        ->condition('fb.deleted', 0)
        ->range(0, $quotedBudget);
      $query->innerJoin('node__field_ol_session', 'fs', 'fs.entity_id = fb.field_featured_bill_target_id AND fs.field_ol_session_value = :session', [':session' => $session]);
      $quoted = $query->execute()->fetchCol();
      $nids += array_combine($quoted, $quoted);
    }

    $remaining = $limit - count($nids);
    if ($remaining > 0) {
      $query = $this->db->select('node_field_data', 'n')
        ->fields('n', ['nid'])
        ->condition('n.type', 'bill')
        ->condition('n.status', 1)
        ->orderRandom()
        ->range(0, $remaining + count($nids));
      $query->innerJoin('node__field_ol_session', 'fs', 'fs.entity_id = n.nid AND fs.field_ol_session_value = :session', [':session' => $session]);
      if ($nids) {
        $query->condition('n.nid', $nids, 'NOT IN');
      }
      $random = $query->execute()->fetchCol();
      foreach ($random as $nid) {
        if (count($nids) >= $limit) {
          break;
        }
        $nids[$nid] = $nid;
      }
    }

    return array_values($nids);
  }

  /**
   * Loads a comma-separated string of existing global_issues term names.
   *
   * @param int[] $nids
   *   Bill node ids.
   *
   * @return array<int, string>
   *   Bill nid => comma-separated existing term names, for bills that have
   *   any. Bills with none are omitted.
   */
  protected function getExistingTagNames(array $nids): array {
    if (!$nids) {
      return [];
    }

    $rows = $this->db->select('node__field_global_issues', 'fg')
      ->fields('fg', ['entity_id', 'field_global_issues_target_id'])
      ->condition('fg.entity_id', $nids, 'IN')
      ->execute();

    $tidsByNid = [];
    foreach ($rows as $row) {
      $tidsByNid[$row->entity_id][] = (int) $row->field_global_issues_target_id;
    }
    if (!$tidsByNid) {
      return [];
    }

    $allTids = array_unique(array_merge(...array_values($tidsByNid)));
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($allTids);

    $names = [];
    foreach ($tidsByNid as $nid => $tids) {
      $names[$nid] = implode(', ', array_map(
        fn (int $tid) => $terms[$tid] ? $terms[$tid]->label() : "tid:$tid",
        $tids
      ));
    }
    return $names;
  }

}
