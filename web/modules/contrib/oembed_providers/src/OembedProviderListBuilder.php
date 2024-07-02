<?php

namespace Drupal\oembed_providers;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of custom oEmbed providers.
 */
class OembedProviderListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Provider');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'weight' => 15,
      'url' => $entity->toUrl('edit-form'),
    ];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'weight' => 15,
      'url' => $entity->toUrl('delete-form'),
    ];

    return $operations;
  }

}
