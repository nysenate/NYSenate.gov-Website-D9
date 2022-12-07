<?php

namespace Drupal\webform_views\Plugin\views\field;

use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\ResultRow;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Reasonable starting point for fields regarding source entity.
 */
trait WebformSubmissionSubmittedToTrait {

  use EntityTranslationRenderTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing, just override the parent.
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->getSourceEntity($this->getView()->result[$this->getView()->row_index])->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * Retrieve the "submitted to" entity from a result row.
   *
   * @param \Drupal\views\ResultRow $row
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Source entity of this submission is submitted to or NULL should it not
   *   have one
   */
  protected function getSourceEntity(ResultRow $row) {
    /** @var WebformSubmissionInterface $entity */
    $entity = $this->getEntity($row);
    return $entity->getSourceEntity();
  }

}
