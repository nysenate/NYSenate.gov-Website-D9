<?php

namespace Drupal\nys_moderation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * ListBuilder for Moderation Test Sets.
 */
class ModerationTestSetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['automator'] = $this->t('Automator');
    $header['entity_count'] = $this->t('Entity Count');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * Make the ID field a link to edit the set.
   * Get the automator label, and target count.
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestSet $entity */
    // If the automator cannot be found, this row is bad.
    try {
      $auto_label = $entity->getAutomator()->label();
    }
    catch (\Throwable) {
      $auto_label = "UNDEFINED!";
    }

    $link_text = $entity->label() . "\n(" . $entity->id() . ")";
    try {
      $link = $entity->toLink($link_text, 'edit-form');
    }
    catch (\Throwable) {
      $link = $link_text;
    }
    $row = [
      'id' => $link,
      'automator' => $auto_label,
      'entity_count' => count($entity->getTargetList() ?? []),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Add a button to create a new test set.
   */
  public function render(): array {
    $build = parent::render();
    if ($this->entityType->hasLinkTemplate('add-form')) {
      $build['add_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Add new @type', ['@type' => $this->entityType->getSingularLabel()]),
        '#url' => Url::fromRoute('entity.' . $this->entityType->id() . '.add_form'),
        '#attributes' => ['class' => ['button', 'button--primary']],
        '#weight' => -10,
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Add the "Run" operation.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    $operations['run_test_set'] = [
      'title' => $this->t('Run'),
      'url' => Url::fromRoute('entity.moderation_test_set.run', ['moderation_test_set' => $entity->id()]),
      'weight' => 100,
    ];

    return $operations;
  }

}
