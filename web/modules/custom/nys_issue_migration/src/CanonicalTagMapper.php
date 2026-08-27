<?php

namespace Drupal\nys_issue_migration;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Resolves old "issues" vocabulary tags to canonical global_issues terms.
 *
 * Built on tag_classification_final_2026-08-06.csv - the mapping produced
 * for the original (never fully completed) issues vocabulary consolidation.
 * That CSV's canonical_term/name values are matched by name against the
 * global_issues vocabulary; a handful of names differ between the two and
 * are corrected via NAME_OVERRIDES.
 */
class CanonicalTagMapper {

  const DEFAULT_CSV = 'migration-data/issue-tags/tag_classification_final_2026-08-06.csv';

  /**
   * Old canonical concept name => actual global_issues term name.
   *
   * For concepts that were renamed and no longer match by name alone.
   */
  const NAME_OVERRIDES = [
    'reproductive rights' => 'Reproductive Health',
  ];

  /**
   * Source "issues" tid => canonical concept name, overriding the CSV.
   *
   * Overrides the CSV's own canonical_term value for that row. The
   * classification CSV's algorithmic keyword-match routed a handful
   * of source tags to a more generic canonical term even though the
   * source tag's own name exactly matches a *different*, more specific
   * canonical term (e.g. the "Reproductive Health" tag itself was routed
   * to "Health Care" instead of the "Reproductive Health" canonical term
   * that already exists). Found via a self-name-mismatch audit: every
   * merge_to_canonical row whose source name normalizes to match one of
   * the 77 canonical names, but was routed elsewhere. Only the two below
   * matched that pattern - this is not a general-purpose reclassification
   * of the CSV's ~3,031 keyword_match/medium rows, which were not
   * individually reviewed.
   */
  const SOURCE_TID_OVERRIDES = [
    // Tid 8932, "Reproductive Health" (old issues vocabulary).
    8932 => 'Reproductive Health',
    // Tid 102935, "Women's Health" (old issues vocabulary).
    102935 => "Women's Health",
  ];

  /**
   * Source "issues" tid => canonical concept name, from the CSV.
   */
  protected array $sourceTidToCanonicalName = [];

  /**
   * Lowercased global_issues term name => tid.
   */
  protected array $canonicalNameToGlobalIssuesTid = [];

  /**
   * Canonical concept names seen in the CSV with no matching term.
   *
   * E.g. "Veterans Hall of Fame", "Women of Distinction".
   */
  protected array $unmappedCanonicalNames = [];

  /**
   * Constructs the CanonicalTagMapper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Loads the CSV mapping and the current global_issues term list.
   *
   * @param string $csvPath
   *   Absolute path to the classification CSV.
   */
  public function load(string $csvPath): void {
    $this->buildGlobalIssuesNameMap();

    $handle = fopen($csvPath, 'r');
    $headers = fgetcsv($handle);
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (count($data) !== count($headers)) {
        continue;
      }
      $row = array_combine($headers, $data);
      $disposition = $row['final_disposition'] ?? '';
      $sourceTid = (int) ($row['tid'] ?? 0);
      if (!$sourceTid) {
        continue;
      }

      $canonicalName = NULL;
      if ($disposition === 'merge_to_canonical') {
        $canonicalName = trim($row['canonical_term'] ?? '');
      }
      elseif ($disposition === 'already_canonical') {
        $canonicalName = trim($row['name'] ?? '');
      }
      // 'delete' disposition rows are intentionally left unmapped - those
      // are non-substantive tags (one-off admin categories and the like).
      if ($canonicalName === NULL || $canonicalName === '') {
        continue;
      }

      $this->sourceTidToCanonicalName[$sourceTid] = $canonicalName;
    }
    fclose($handle);
  }

  /**
   * Builds the lowercased global_issues term name => tid lookup.
   */
  protected function buildGlobalIssuesNameMap(): void {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $ids = $storage->getQuery()->condition('vid', 'global_issues')->accessCheck(FALSE)->execute();
    foreach ($storage->loadMultiple($ids) as $term) {
      $this->canonicalNameToGlobalIssuesTid[strtolower($term->getName())] = (int) $term->id();
    }
  }

  /**
   * Resolves a source "issues" vocabulary tid to a global_issues tid.
   *
   * @param int $sourceTid
   *   The old "issues" vocabulary term id.
   *
   * @return int|null
   *   The matching global_issues tid, or NULL if the source tid has no
   *   mapping (delete disposition, unrecognized tid) or its canonical
   *   name has no matching global_issues term.
   */
  public function resolve(int $sourceTid): ?int {
    $canonicalName = self::SOURCE_TID_OVERRIDES[$sourceTid]
      ?? $this->sourceTidToCanonicalName[$sourceTid]
      ?? NULL;
    if ($canonicalName === NULL) {
      return NULL;
    }
    $lookupName = strtolower(self::NAME_OVERRIDES[strtolower($canonicalName)] ?? $canonicalName);
    if (!isset($this->canonicalNameToGlobalIssuesTid[$lookupName])) {
      $this->unmappedCanonicalNames[$canonicalName] = TRUE;
      return NULL;
    }
    return $this->canonicalNameToGlobalIssuesTid[$lookupName];
  }

  /**
   * Canonical concept names from the CSV with no matching global_issues term.
   *
   * @return string[]
   *   Unmapped canonical concept names encountered so far.
   */
  public function getUnmappedCanonicalNames(): array {
    return array_keys($this->unmappedCanonicalNames);
  }

}
