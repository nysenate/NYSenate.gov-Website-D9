<?php

namespace Drupal\oembed_providers;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of custom oEmbed provider buckets.
 */
class OembedProviderBucketListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['markup'] = [
      '#markup' => $this->t('<p>Provider buckets allow site builders to define groups of oEmbed providers. These buckets are automatically exposed as media sources.</p>'),
      '#weight' => -10,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Bucket');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->get('description');
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
