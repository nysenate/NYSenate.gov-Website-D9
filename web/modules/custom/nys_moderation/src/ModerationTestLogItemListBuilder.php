<?php

namespace Drupal\nys_moderation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder for Moderation Test Log Items.
 */
class ModerationTestLogItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [
      'id' => $this->t('ID'),
      'entity' => $this->t('Entity'),
      'expected' => $this->t('Pass Expected?'),
      'passed' => $this->t('Passed?'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * Make the ID field a link to edit the set.
   * Get the automator label, and target count.
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestLogItem $entity */
    $row = [
      'id' => $entity->id(),
      'entity' => $entity->get('entity_type') . ':' . $entity->get('entity_id'),
      'expected' => $entity->get('expected'),
      'passed' => $entity->get('passed'),
    ];

    return $row + parent::buildRow($entity);
  }

}
