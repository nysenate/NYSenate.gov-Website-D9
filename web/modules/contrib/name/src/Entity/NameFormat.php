<?php

namespace Drupal\name\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\name\NameFormatInterface;

/**
 * Defines the Name Format configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "name_format",
 *   label = @Translation("Name format"),
 *   handlers = {
 *     "access" = "Drupal\name\NameFormatAccessController",
 *     "list_builder" = "Drupal\name\NameFormatListBuilder",
 *     "form" = {
 *       "add" = "Drupal\name\Form\NameFormatForm",
 *       "edit" = "Drupal\name\Form\NameFormatForm",
 *       "delete" = "Drupal\name\Form\NameFormatDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\name\NameFormatListBuilder"
 *   },
 *   config_prefix = "name_format",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "status",
 *     "langcode",
 *     "locked",
 *     "pattern",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/regional/name/manage/{name_format}",
 *     "delete-form" = "/admin/config/regional/name/manage/{name_format}/delete"
 *   }
 * )
 */
class NameFormat extends ConfigEntityBase implements NameFormatInterface {

  /**
   * The name format machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The name format UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the name format entity.
   *
   * @var string
   */
  public $label;

  /**
   * The name format pattern.
   *
   * @var array
   */
  public $pattern;

  /**
   * The locked status of this name format.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return [
      'path' => 'admin/config/regional/name/manage/' . $this->id(),
      'options' => [
        'entity_type' => $this->getEntityType(),
        'entity' => $this,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

}
