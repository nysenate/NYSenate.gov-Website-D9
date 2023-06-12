<?php

namespace Drupal\Tests\rh_taxonomy\Functional;

use Drupal\Tests\rabbit_hole\Functional\RabbitHoleBehaviorInvocationTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Test that rabbit hole behaviors are invoked correctly for taxonomy terms.
 *
 * @group rh_taxonomy
 */
class TaxonomyBehaviorInvocationTest extends RabbitHoleBehaviorInvocationTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rh_taxonomy', 'taxonomy'];

  /**
   * Taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function createEntityBundle($action = NULL) {
    $this->vocabulary = $this->createVocabulary();
    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings([
        'action' => $action,
        'allow_override' => TRUE,
      ], 'taxonomy_vocabulary', $this->vocabulary->id());
    }
    return $this->vocabulary->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($action = NULL) {
    $values = [];
    if (isset($action)) {
      $values['rh_action'] = $action;
    }
    return $this->createTerm($this->vocabulary, $values);
  }

}
