<?php

namespace Drupal\webform_views_extras;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Webform submission relationships entities.
 */
class WebformSubmissionRelationshipsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Webform submission relationships');
    $header['id'] = $this->t('Machine name');
    $header['content_entity_type_id'] = $this->t('Content entity type Id');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['content_entity_type_id'] = $entity->getContentEntityTypeId();
    return $row + parent::buildRow($entity);
  }

}
