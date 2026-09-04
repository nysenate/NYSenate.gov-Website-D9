<?php

namespace Drupal\nys_issue_migration\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\nys_issue_migration\CanonicalTagMapper;
use Drush\Commands\DrushCommands;

/**
 * Drush command to backfill field_global_issues from field_issues.
 *
 * For every non-bill node with field_issues populated and
 * field_global_issues still empty, resolves each field_issues tag to a
 * canonical global_issues term (via CanonicalTagMapper) and writes the
 * distinct result directly into node__field_global_issues /
 * node_revision__field_global_issues on the node's current default
 * revision - a backend backfill, not an editorial change, so it
 * deliberately does not create a new revision.
 *
 * field_global_issues has a cardinality of 5; if more than 5 distinct
 * canonical terms would apply to a node, the first 5 are written and the
 * rest are reported in the truncation log (field_issues itself is
 * capped at 5, so this is not expected to trigger against current data).
 *
 * Usage:
 *   drush global-issues-tag-content                  # dry-run
 *   drush global-issues-tag-content --commit          # write
 */
class GlobalIssuesTagContentCommands extends DrushCommands {

  const CARDINALITY = 5;
  const BATCH_SIZE = 500;

  /**
   * Constructs the GlobalIssuesTagContentCommands object.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected Connection $db,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Backfill field_global_issues from field_issues on non-bill content.
   *
   * Runs as a dry-run by default. Pass --commit to write. Safe to re-run:
   * only processes nodes where field_global_issues is currently empty.
   *
   * @command global-issues-tag-content
   * @option csv Path to the classification CSV.
   * @option commit Write changes. Without this flag, reports counts only.
   * @usage drush global-issues-tag-content
   *   Dry-run: report how many nodes would be tagged.
   * @usage drush global-issues-tag-content --commit
   *   Write field_global_issues on qualifying nodes.
   */
  public function tag(
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

    $counts = [
      'nodes_processed' => 0,
      'nodes_tagged' => 0,
      'nodes_no_mapping' => 0,
      'nodes_truncated' => 0,
    ];
    $truncatedExamples = [];
    $byType = [];
    $touchedNids = [];

    $lastNid = 0;
    while (TRUE) {
      $candidateRows = $this->db->select('node__field_issues', 'fi')
        ->fields('fi', ['entity_id'])
        ->condition('fi.deleted', 0)
        ->condition('fi.entity_id', $lastNid, '>')
        ->orderBy('fi.entity_id')
        ->distinct()
        ->range(0, self::BATCH_SIZE)
        ->execute()
        ->fetchCol();

      if (empty($candidateRows)) {
        break;
      }
      $lastNid = end($candidateRows);

      $nodeInfo = $this->db->select('node_field_data', 'n')
        ->fields('n', ['nid', 'vid', 'type', 'langcode', 'status', 'sticky', 'created'])
        ->condition('n.nid', $candidateRows, 'IN')
        ->condition('n.type', 'bill', '!=')
        ->execute()
        ->fetchAllAssoc('nid');

      if (!$nodeInfo) {
        continue;
      }

      $alreadyTagged = $this->db->select('node__field_global_issues', 'fg')
        ->fields('fg', ['entity_id'])
        ->condition('fg.entity_id', array_keys($nodeInfo), 'IN')
        ->execute()
        ->fetchCol();
      $alreadyTagged = array_flip($alreadyTagged);

      $issuesByNode = $this->db->select('node__field_issues', 'fi')
        ->fields('fi', ['entity_id', 'field_issues_target_id'])
        ->condition('fi.deleted', 0)
        ->condition('fi.entity_id', array_keys($nodeInfo), 'IN')
        ->orderBy('fi.delta')
        ->execute();
      $groupedIssues = [];
      foreach ($issuesByNode as $row) {
        $groupedIssues[$row->entity_id][] = (int) $row->field_issues_target_id;
      }

      foreach ($nodeInfo as $nid => $node) {
        if (isset($alreadyTagged[$nid])) {
          continue;
        }
        $counts['nodes_processed']++;

        $sourceTids = $groupedIssues[$nid] ?? [];
        $resolved = [];
        foreach ($sourceTids as $sourceTid) {
          $targetTid = $mapper->resolve($sourceTid);
          if ($targetTid !== NULL && !in_array($targetTid, $resolved, TRUE)) {
            $resolved[] = $targetTid;
          }
        }

        if (empty($resolved)) {
          $counts['nodes_no_mapping']++;
          continue;
        }

        if (count($resolved) > self::CARDINALITY) {
          $counts['nodes_truncated']++;
          if (count($truncatedExamples) < 25) {
            $truncatedExamples[] = "nid {$nid} ({$node->type}): " . count($resolved) . ' distinct terms, kept first ' . self::CARDINALITY;
          }
          $resolved = array_slice($resolved, 0, self::CARDINALITY);
        }

        if ($commit) {
          $delta = 0;
          foreach ($resolved as $targetTid) {
            $this->db->insert('node__field_global_issues')
              ->fields([
                'bundle' => $node->type,
                'deleted' => 0,
                'entity_id' => $nid,
                'revision_id' => $node->vid,
                'langcode' => $node->langcode,
                'delta' => $delta,
                'field_global_issues_target_id' => $targetTid,
              ])
              ->execute();
            $this->db->insert('node_revision__field_global_issues')
              ->fields([
                'bundle' => $node->type,
                'deleted' => 0,
                'entity_id' => $nid,
                'revision_id' => $node->vid,
                'langcode' => $node->langcode,
                'delta' => $delta,
                'field_global_issues_target_id' => $targetTid,
              ])
              ->execute();
            $delta++;
          }

          // node__field_global_issues alone isn't enough - Views'
          // term_node_tid relationship (used by the issue page displays)
          // reads from taxonomy_index, not the field tables directly.
          // Core normally maintains that via taxonomy_build_node_index()
          // on node save, which this direct-SQL write bypasses. Mirror it
          // here: one row per (nid, tid), published default-revision
          // nodes only, matching core's own condition exactly.
          if ($node->status) {
            foreach ($resolved as $targetTid) {
              $this->db->merge('taxonomy_index')
                ->keys(['nid' => $nid, 'tid' => $targetTid])
                ->fields([
                  'status' => $node->status,
                  'sticky' => $node->sticky,
                  'created' => $node->created,
                ])
                ->execute();
            }
          }

          $touchedNids[] = $nid;
        }

        $counts['nodes_tagged']++;
        $byType[$node->type] = ($byType[$node->type] ?? 0) + 1;
      }

      if (($counts['nodes_processed'] % 5000) < self::BATCH_SIZE) {
        $this->io()->text("  Processed {$counts['nodes_processed']} nodes...");
      }
    }

    if ($commit && $touchedNids) {
      $tags = array_map(fn($nid) => 'node:' . $nid, $touchedNids);
      Cache::invalidateTags($tags);
    }

    $this->io()->newLine();
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Nodes processed (field_issues set, field_global_issues empty)', number_format($counts['nodes_processed'])],
        ['Nodes ' . ($commit ? 'tagged' : 'that would be tagged'), number_format($counts['nodes_tagged'])],
        ['Nodes with no canonical mapping for any of their tags', number_format($counts['nodes_no_mapping'])],
        ['Nodes truncated to 5 terms', number_format($counts['nodes_truncated'])],
      ]
    );

    if ($byType) {
      ksort($byType);
      $this->io()->table(
        ['Content type', 'Nodes tagged'],
        array_map(fn($type, $count) => [$type, number_format($count)], array_keys($byType), $byType)
      );
    }

    if ($truncatedExamples) {
      $this->io()->warning("Truncated to 5 terms:\n" . implode("\n", $truncatedExamples));
    }

    $unmapped = $mapper->getUnmappedCanonicalNames();
    if ($unmapped) {
      $this->io()->warning('Canonical concept names with no matching global_issues term: ' . implode(', ', $unmapped));
    }

    if (!$commit) {
      $this->io()->note('Run with --commit to write these changes.');
    }
    else {
      $this->io()->success(sprintf('Tagged %d nodes with field_global_issues.', $counts['nodes_tagged']));
    }
  }

}
