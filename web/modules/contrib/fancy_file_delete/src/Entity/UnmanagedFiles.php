<?php

namespace Drupal\fancy_file_delete\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Unmanaged Files entity.
 *
 * @ingroup fancy_file_delete
 *
 * @ContentEntityType(
 *   id = "unmanaged_files",
 *   label = @Translation("Unmanaged Files"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fancy_file_delete\Entity\UnmanagedFilesListBuilder",
 *     "views_data" = "Drupal\fancy_file_delete\Entity\UnmanagedFilesViewsData",
 *     "translation" = "Drupal\fancy_file_delete\Entity\UnmanagedFilesTranslationHandler",
 *     "access" = "Drupal\fancy_file_delete\Entity\UnmanagedFilesAccessControlHandler",
 *   },
 *   base_table = "unmanaged_files",
 *   admin_permission = "administer unmanaged files entities",
 *   entity_keys = {
 *     "id" = "unfid",
 *     "label" = "path",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class UnmanagedFiles extends ContentEntityBase implements UnmanagedFilesInterface {

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->get('path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($filename) {
    $this->get('path')->value = $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('Path of the file.'));


    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
