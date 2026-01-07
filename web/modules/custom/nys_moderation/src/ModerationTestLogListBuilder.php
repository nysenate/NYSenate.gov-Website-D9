<?php

namespace Drupal\nys_moderation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * ListBuilder for Moderation Test Logs.
 */
class ModerationTestLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['run_by'] = $this->t('Run By');
    $header['entity_count'] = $this->t('Entity Count');
    $header['pass_flag'] = $this->t('Pass/Flag');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * Make the ID field a link to edit the set.
   * Get the automator label, and target count.
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\nys_moderation\Entity\ModerationTestLog $entity */
    // If the automator cannot be found, this row is bad.
    try {
      $run_by = $entity->get('uid')->entity;
    }
    catch (\Throwable) {
      $run_by = "UNDEFINED!";
    }

    try {
      $link = $entity->toLink($entity->label(), 'edit-form');
    }
    catch (\Throwable) {
      $link = $entity->label();
    }

    $stats = $entity->getPassFail();
    $stat = "Expected: " . $stats['expected']['pass'] . "/" .
      $stats['expected']['flag'] . "\nActual: " . $stats['actual']['pass'] .
      "/" . $stats['actual']['flag'];

    $row = [
      'id' => $entity->id(),
      'name' => $link,
      'run_by' => ($run_by instanceof UserInterface ? $run_by->toLink() : $run_by),
      'entity_count' => $entity->itemCount(),
      'pass_flag' => $stat,
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Add the "Run" operation.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    $operations['re_run_test_set'] = [
      'title' => $this->t('Run Again'),
      'url' => Url::fromRoute('entity.moderation_test_set.run', ['moderation_test_set' => $entity->id()]),
      'weight' => 100,
    ];

    return $operations;
  }

}
