<?php

namespace Drupal\webform_views_extras\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Webform submission relationships entity.
 *
 * @ConfigEntityType(
 *   id = "webform_submission_relationships",
 *   label = @Translation("Webform submission relationships"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_views_extras\WebformSubmissionRelationshipsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_views_extras\Form\WebformSubmissionRelationshipsForm",
 *       "edit" = "Drupal\webform_views_extras\Form\WebformSubmissionRelationshipsForm",
 *       "delete" = "Drupal\webform_views_extras\Form\WebformSubmissionRelationshipsDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webform_views_extras\WebformSubmissionRelationshipsHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webform_submission_relationships",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webform_submission_relationships/{webform_submission_relationships}",
 *     "add-form" = "/admin/structure/webform_submission_relationships/add",
 *     "edit-form" = "/admin/structure/webform_submission_relationships/{webform_submission_relationships}/edit",
 *     "delete-form" = "/admin/structure/webform_submission_relationships/{webform_submission_relationships}/delete",
 *     "collection" = "/admin/structure/webform_submission_relationships"
 *   }
 * )
 */
class WebformSubmissionRelationships extends ConfigEntityBase implements WebformSubmissionRelationshipsInterface {

  /**
   * The Webform submission relationships ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webform submission relationships label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Webform submission relationships entity type.
   *
   * @var string
   */
  protected $content_entity_type_id;


  /**
   * @inheritDoc
   */
  function getContentEntityTypeId() {
    return $this->content_entity_type_id;
  }
}
