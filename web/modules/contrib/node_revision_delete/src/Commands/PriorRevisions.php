<?php

namespace Drupal\node_revision_delete\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node_revision_delete\NodeRevisionDeleteInterface;
use Drush\Commands\DrushCommands;

/**
 * The Prior Revision Delete Commands.
 *
 * @package Drupal\node_revision_delete\Commands
 */
class PriorRevisions extends DrushCommands {

  /**
   * The NodeRevisionDelete service.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDeleteInterface
   */
  protected NodeRevisionDeleteInterface $nodeRevisionDelete;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * NodeRevisionDeleteCommands constructor.
   *
   * @param \Drupal\node_revision_delete\NodeRevisionDeleteInterface $nodeRevisionDelete
   *   The NodeRevisionDelete service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManager service.
   */
  public function __construct(
    NodeRevisionDeleteInterface $nodeRevisionDelete,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->nodeRevisionDelete = $nodeRevisionDelete;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Deletes all revisions prior to a revision.
   *
   * @param int $nid
   *   The id of the node which revisions will be deleted.
   * @param int $vid
   *   The revision id, all prior revisions to this revision will be deleted.
   *
   * @usage nrd-delete-prior-revisions 1 3
   *   Delete all revisions prior to revision id 3 of node id 1.
   *
   * @command nrd:delete-prior-revisions
   *
   * @aliases nrd-dpr,nrd-delete-prior-revisions
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deletePriorRevisions(int $nid = 0, int $vid = 0): void {
    // Get list of prior revisions.
    $previousRevisions = $this->nodeRevisionDelete->getPreviousRevisions($nid, $vid);

    if (count($previousRevisions) === 0) {
      $this->output()->writeln(dt('<error>No prior revision(s) found to delete.</error>'));
      return;
    }

    if ($this->io()->confirm(dt('Confirm deleting @count revision(s)?', ['@count' => count($previousRevisions)]))) {
      // Check if current revision should be deleted, too.
      if ($this->io()->confirm(dt('Additionally, do you want to delete the revision @vid? @count revision(s) will be deleted.', [
        '@vid' => $vid,
        '@count' => count($previousRevisions) + 1,
      ]))) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($vid);
      }

      foreach ($previousRevisions as $revision) {
        $this->entityTypeManager->getStorage('node')->deleteRevision($revision->getRevisionId());
      }
    }
  }

  /**
   * Validate inputs before executing the drush command nrd-dpr.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data.
   *
   * @return bool
   *   Returns TRUE if the validations has passed FALSE otherwise.
   *
   * @hook validate nrd-delete-prior-revisions
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deletePriorRevisionsValidate(CommandData $commandData): bool {
    $input = $commandData->input();
    $nid = $input->getArgument('nid');
    $vid = $input->getArgument('vid');

    // Check if argument nid is a valid node id.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    if (is_null($node)) {
      $this->io()->error(dt('@nid is not a valid node id.', ['@nid' => $nid]));
      return FALSE;
    }

    // Get all revisions of the current node, in all languages.
    $revision_ids = $node_storage->revisionIds($node);
    if (!in_array($vid, $revision_ids)) {
      $variables = [
        '@vid' => $vid,
        '@nid' => $nid,
      ];

      $this->io()->error(dt('@vid is not a valid revision id for node @nid.', $variables));
      return FALSE;
    }

    return TRUE;
  }

}
