<?php

namespace Drupal\config_split\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Configuration Split setting entity.
 *
 * @ConfigEntityType(
 *   id = "config_split",
 *   label = @Translation("Configuration Split setting"),
 *   handlers = {
 *     "view_builder" = "Drupal\config_split\ConfigSplitEntityViewBuilder",
 *     "list_builder" = "Drupal\config_split\ConfigSplitEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\config_split\Form\ConfigSplitEntityForm",
 *       "edit" = "Drupal\config_split\Form\ConfigSplitEntityForm",
 *       "delete" = "Drupal\config_split\Form\ConfigSplitEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\config_split\ConfigSplitEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "config_split",
 *   admin_permission = "administer configuration split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/configuration/config-split/{config_split}",
 *     "add-form" = "/admin/config/development/configuration/config-split/add",
 *     "edit-form" = "/admin/config/development/configuration/config-split/{config_split}/edit",
 *     "delete-form" = "/admin/config/development/configuration/config-split/{config_split}/delete",
 *     "enable" = "/admin/config/development/configuration/config-split/{config_split}/enable",
 *     "disable" = "/admin/config/development/configuration/config-split/{config_split}/disable",
 *     "collection" = "/admin/config/development/configuration/config-split"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "folder",
 *     "module",
 *     "theme",
 *     "blacklist",
 *     "graylist",
 *     "graylist_dependents",
 *     "graylist_skip_equal",
 *     "weight",
 *     "status",
 *   }
 * )
 */
class ConfigSplitEntity extends ConfigEntityBase implements ConfigSplitEntityInterface {

  /**
   * The Configuration Split setting ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Configuration Split setting label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Configuration Split setting description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The folder to export to.
   *
   * @var string
   */
  protected $folder = '';

  /**
   * The modules to split.
   *
   * @var array
   */
  protected $module = [];

  /**
   * The themes to split.
   *
   * @var array
   */
  protected $theme = [];

  /**
   * The explicit configuration to filter out.
   *
   * @var string[]
   */
  protected $blacklist = [];

  /**
   * The configuration to ignore.
   *
   * @var string[]
   */
  protected $graylist = [];

  /**
   * Include the graylist dependents flag.
   *
   * @var bool
   */
  protected $graylist_dependents = TRUE;

  /**
   * Skip graylisted config without a change flag.
   *
   * @var bool
   */
  protected $graylist_skip_equal = TRUE;

  /**
   * The weight of the configuration when splitting several folders.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The status, whether to be used by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

}
