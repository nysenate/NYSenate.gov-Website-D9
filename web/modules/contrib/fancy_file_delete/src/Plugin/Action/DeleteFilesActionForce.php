<?php

namespace Drupal\fancy_file_delete\Plugin\Action;

use Drupal\fancy_file_delete\Entity\UnmanagedFiles;
use Drupal\file\Entity\File;

/**
 * Force Deletes Files.
 *
 * @Action(
 *   id = "delete_files_action_force",
 *   label = @Translation("FORCE Delete Files (No Turning Back!)"),
 *   type = "",
 *   pass_view = TRUE
 * )
 */
class DeleteFilesActionForce extends DeleteFilesAction {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Set entities to batch our way.
    $values = [];
    foreach ($entities as $entity) {
      if ($entity instanceof UnmanagedFiles) {
        $values[] = $entity->getPath();
      }
      elseif ($entity instanceof File) {
        $values[] = $entity->id();
      }
    }
    // Send to batch.
    $this->batch->setBatch($values, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }
}
