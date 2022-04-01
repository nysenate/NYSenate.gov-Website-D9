<?php

namespace Drupal\taxonomy_access_fix;

use Drupal\taxonomy\VocabularyListBuilder as VocabularyListBuilderBase;

/**
 * Builds administrative lists of Taxonomy Vocabulary entities.
 */
class VocabularyListBuilder extends VocabularyListBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    // Remove vocabularies the current user doesn't have any access for.
    foreach ($entities as $id => $entity) {
      if (!$entity->access('view')) {
        unset($entities[$id]);
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Remove vocabulary sorting for non-admins.
    if (!$this->currentUser->hasPermission('administer taxonomy')) {
      unset($this->weightKey);
    }
    return parent::render();
  }

}
