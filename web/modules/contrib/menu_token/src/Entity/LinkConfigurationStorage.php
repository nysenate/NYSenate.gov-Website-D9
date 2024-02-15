<?php

namespace Drupal\menu_token\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Link configuration storage entity.
 *
 * @ConfigEntityType(
 *   id = "link_configuration_storage",
 *   label = @Translation("Link configuration storage"),
 *   handlers = {
 *     "list_builder" = "Drupal\menu_token\LinkConfigurationStorageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\menu_token\Form\LinkConfigurationStorageForm",
 *       "edit" = "Drupal\menu_token\Form\LinkConfigurationStorageForm",
 *       "delete" = "Drupal\menu_token\Form\LinkConfigurationStorageDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\menu_token\LinkConfigurationStorageHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "link_configuration_storage",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "linkid" = "linkid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/link_configuration_storage/{link_configuration_storage}",
 *     "add-form" = "/admin/structure/link_configuration_storage/add",
 *     "edit-form" = "/admin/structure/link_configuration_storage/{link_configuration_storage}/edit",
 *     "delete-form" = "/admin/structure/link_configuration_storage/{link_configuration_storage}/delete",
 *     "collection" = "/admin/structure/link_configuration_storage"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "linkid",
 *     "uuid"
 *   }
 * )
 */
class LinkConfigurationStorage extends ConfigEntityBase implements LinkConfigurationStorageInterface {

  /**
   * The Link configuration storage ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Link configuration storage label.
   *
   * @var string
   */
  protected $label;


  /**
   * The link id.
   *
   * @var string
   */
  public $linkid;

  /**
   * Serialized field of config values for stored link.
   *
   * @var string
   */
  public $configurationSerialized;

}
