<?php

namespace Drupal\name\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\name\NameListFormatInterface;

/**
 * Defines the Name List Format configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "name_list_format",
 *   label = @Translation("Name list format"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\name\Form\NameListFormatForm",
 *       "edit" = "Drupal\name\Form\NameListFormatForm",
 *       "delete" = "Drupal\name\Form\NameListFormatDeleteConfirm"
 *     },
 *     "access" = "Drupal\name\NameFormatAccessController",
 *     "list_builder" = "Drupal\name\NameListFormatListBuilder"
 *   },
 *   config_prefix = "name_list_format",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "langcode",
 *     "label",
 *     "locked",
 *     "status",
 *     "delimiter",
 *     "and",
 *     "delimiter_precedes_last",
 *     "el_al_min",
 *     "el_al_first",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/regional/name/list/manage/{name_format}",
 *     "delete-form" = "/admin/config/regional/name/list/manage/{name_format}/delete"
 *   }
 * )
 */
class NameListFormat extends ConfigEntityBase implements NameListFormatInterface {

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
   * The locked status of this name format.
   *
   * @var bool
   */
  public $locked = FALSE;

  /**
   * The delimiter of this name list format.
   *
   * @var string
   */
  public $delimiter = ', ';

  /**
   * The final delimitor type of this name list format.
   *
   * Valid options include:
   * - text: textual (i.e. 'and').
   * - symbol: ampersand.
   *
   * @var string
   */
  public $and = 'text';

  /**
   * The method of handling the final delimiter before the and indicator.
   *
   * Valid options include:
   * - never: Never combine
   * - always: Always combine
   * - contextual: Combine with 3 or more names.
   *
   * @var string
   */
  public $delimiter_precedes_last = 'never';

  /**
   * Reduce list limit of this name list format.
   *
   * This specifies a limit on the number of names to display. After this
   * limit, names are removed and the abbreviation et al is appended. This
   * Latin abbreviation of et alii means "and others".
   *
   * @var int
   */
  public $el_al_min = 3;

  /**
   * Number of names to show when list is reduced of this name list format.
   *
   * This specifies a limit on the number of names to display. After this
   * limit, names are removed and the abbreviation et al is appended. This
   * Latin abbreviation of et alii means "and others".
   *
   * @var int
   */
  public $el_al_first = 1;

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return [
      'path' => 'admin/config/regional/name/list/manage/' . $this->id(),
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

  /**
   * {@inheritdoc}
   */
  public function listSettings() {
    $el_al_first = $this->el_al_first;
    if ($el_al_first > $this->el_al_min) {
      $el_al_first = $this->el_al_min;
    }
    return [
      'delimiter' => $this->delimiter,
      'and' => $this->and,
      'delimiter_precedes_last' => $this->delimiter_precedes_last,
      'el_al_min' => $this->el_al_min,
      'el_al_first' => $el_al_first,
    ];
  }

}
