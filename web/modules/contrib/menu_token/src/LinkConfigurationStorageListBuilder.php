<?php

namespace Drupal\menu_token;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Link configuration storage entities.
 */
class LinkConfigurationStorageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Link configuration storage');
    $header['id'] = $this->t('Machine name');
    $header['linkid'] = $this->t('Link id');
    $header['configurationSerialized'] = $this->t('Serialized configuration');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['linkid'] = $entity->linkid;
    $row['configurationSerialized'] = $entity->configurationSerialized;

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
