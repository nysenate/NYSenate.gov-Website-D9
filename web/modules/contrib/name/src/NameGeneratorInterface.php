<?php

namespace Drupal\name;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface for the various functions required for the examples.
 */
interface NameGeneratorInterface {

  /**
   * Service to generate random names.
   *
   * @param int $limit
   *   The number to generate.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition if in context.
   *
   * @return array
   *   An array of name components.
   */
  public function generateSampleNames($limit = 3, FieldDefinitionInterface $field_definition = NULL);

  /**
   * Service to load preconfigured names.
   *
   * @param int $limit
   *   The number to load.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition if in context.
   *
   * @return array
   *   An array of name components.
   */
  public function loadSampleValues($limit = 3, FieldDefinitionInterface $field_definition = NULL);

}
