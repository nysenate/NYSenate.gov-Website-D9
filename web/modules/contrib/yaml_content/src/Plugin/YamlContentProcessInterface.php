<?php

namespace Drupal\yaml_content\Plugin;

/**
 * An interface for all YAML Content process plugins to implement.
 */
interface YamlContentProcessInterface {

  /**
   * Processes field data.
   *
   * @param \Drupal\yaml_content\Plugin\ProcessingContext $context
   *   The processing context.
   * @param array $field_data
   *   The field data.
   *
   * @return array|int
   *   The entity id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Error for missing data.
   *
   * @see \Drupal\yaml_content\Plugin\YamlContentProcessManager::preprocessFieldData()
   */
  public function process(ProcessingContext $context, array &$field_data);

}
