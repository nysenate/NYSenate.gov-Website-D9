<?php

namespace Drupal\search_api_page;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * Provides a listing of Search page entities.
 */
class SearchApiPageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Title');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\search_api_page\SearchApiPageInterface $entity */
    $row['label'] = $entity->label();
    $row['path'] = '';

    $path = $entity->getPath();
    if ($path !== '') {
      $route = sprintf('search_api_page.%s.%s', \Drupal::languageManager()->getDefaultLanguage()->getId(), $entity->id());
      $row['path'] = Link::createFromRoute($path, $route);
    }

    return $row + parent::buildRow($entity);
  }

}
