<?php

namespace Drupal\yaml_content\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Defines a base processor implementation that most processors will extend.
 */
abstract class YamlContentProcessBase extends PluginBase implements YamlContentProcessInterface {

  /**
   * Prepare an error message and throw error.
   *
   * @param string $error_message
   *   The error message to display.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   */
  protected function throwParamError($error_message, $entity_type, array $filter_params) {
    // Build parameter output description for error message.
    $error_params = [
      '[',
      '  "entity_type" => ' . $entity_type . ',',
    ];
    foreach ($filter_params as $key => $value) {
      $error_params[] = sprintf("  '%s' => '%s',", $key, $value);
    }
    $error_params[] = ']';
    $param_output = implode("\n", $error_params);

    throw new MissingDataException(__CLASS__ . ': ' . $error_message . ': ' . $param_output);
  }

}
