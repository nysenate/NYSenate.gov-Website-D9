<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for Number (integer, decimal and float).
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_number",
 * )
 */
class Number extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];

    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        if ($options['field_cardinality'] == 1) {
          $state[$options['state']][$options['selector']] = [
            'value' => $this->getWidgetValue($options['value_form']),
          ];
        }
        else {
          $values = array_column($this->getWidgetValue($options['value_form']), 'value');
          foreach ($values as $key => $value) {
            if (empty($value)) {
              continue;
            }
            $selector = str_replace('[0]', "[{$key}]", $options['selector']);
            $state[$options['state']][$selector] = [
              'value' => $value,
            ];
          }
        }
        return $state;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // Implemented in DefaultStateHandler.
        return $state;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        // Implemented in DefaultStateHandler.
        break;
    }

    return $state;
  }

}
