<?php

namespace Drupal\yaml_content\Plugin\yaml_content\process;

use Drupal\yaml_content\Plugin\ProcessingContext;

/**
 * Plugin for querying and loading a referenced entity URI.
 *
 * @YamlContentProcess(
 *   id = "reference_uri",
 *   title = @Translation("Entity Reference URI Processor"),
 *   description = @Translation("Attach an entity reference URI.")
 * )
 */
class ReferenceUri extends Reference {

  /**
   * {@inheritdoc}
   */
  public function process(ProcessingContext $context, array &$field_data) {
    // Run the reference plugin process method.
    parent::process($context, $field_data);
    // If an entity id was stored in the field_data array.
    if (!empty($field_data['target_id'])) {
      // Get the entity type from the configuration.
      $entity_type = $this->configuration[0];
      // Create an internal path for the referenced entity.
      $uri = 'entity:' . $entity_type . '/' . $field_data['target_id'];
      // Replace 'target_id' with 'uri'.
      unset($field_data['target_id']);
      $field_data['uri'] = $uri;
    }
  }

}
