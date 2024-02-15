<?php

namespace Drupal\fancy_file_delete\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Unmanaged Files entities.
 *
 * @ingroup fancy_file_delete
 */
class UnmanagedFilesListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Unmanaged Files ID');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\fancy_file_delete\Entity\UnmanagedFiles $entity */
    $row['id'] = $entity->id();
    $row['path'] = Link::createFromRoute(
      $entity->label(),
      'entity.unmanaged_files.edit_form',
      ['unmanaged_files' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
