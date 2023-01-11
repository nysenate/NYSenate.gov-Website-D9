<?php

/**
 * @file
 * Contains webform_views_extras.views.inc.
 */

use Drupal\webform_views_extras\Entity\WebformSubmissionRelationships;

/**
 * Implements hook_views_data_alter().
 */
function webform_views_extras_views_data_alter(array &$data) {

  $webform_submissions_entities_relationships = WebformSubmissionRelationships::loadMultiple();
  if (!empty($webform_submissions_entities_relationships)) {
    /**
     * @var \Drupal\webform_views_extras\Entity\WebformSubmissionRelationships $webform_relationship_content_entity
     */
    foreach ($webform_submissions_entities_relationships as $webform_relationship_content_entity) {
      $entity_type = $webform_relationship_content_entity->getContentEntityTypeId();
      if (\Drupal::entityTypeManager()->hasDefinition($entity_type)) {
        $entity_definition = \Drupal::entityTypeManager()->getDefinition($entity_type);
        $relationship = [
          'base' => $entity_definition->getDataTable(),
          'base field' => $entity_definition->getKey('id'),
          'id' => 'standard',
          'label' => t('Submitted to: @entity_type (Webform Views Extras)', ['@entity_type' => $entity_type]),
          'title' => t('Submitted to: @entity_type (Webform Views Extras)', ['@entity_type' => $entity_type]),
          'extra' => [
            ['left_field' => 'entity_type', 'value' => $entity_type],
          ],
        ];

        $data['webform_submission']["entity_id_{$entity_type}"] = [
          'title' => t('Submitted to: @entity_type (Webform Views Extras)', ['@entity_type' => $entity_type]),
          'help' => t("The @entity_type ID of the entity of which this webform submission was submitted from.", ['@entity_type' => $entity_type]),
          'field' => ['id' => "field"],
          'argument' => ['id' => "string"],
          'filter' => ['id' => "string"],
          'sort' => ['id' => "standard"],
          'entity field' => "entity_id_" . $entity_type,
          "relationship" => $relationship,
        ];
      }
    }
  }

}
