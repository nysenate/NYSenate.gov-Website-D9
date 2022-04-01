<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for text fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_default_state",
 * )
 */
class DefaultStateHandler extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    // Build the values that trigger the dependency.
    $values = [];
    $values_array = $this->getConditionValues($options);
    $values_set = $options['values_set'];

    switch ($values_set) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $values[$options['condition']] = $options['value_form'];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $values[$options['condition']] = ['regex' => $options['regex']];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $values[$options['condition']] = ['xor' => $values_array];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        $values[$options['condition']] = count($values_array) == 1 ? $values_array[0] : $values_array;
        break;

      default:
        if ($options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT) {
          $options['state'] = '!' . $options['state'];
        }
        // OR, NOT conditions are obtained with a nested array.
        if (!empty($values_array)) {
          foreach ($values_array as $value) {
            $values[] = ['value' => $value];
          }
        }
        else {
          $values = $options['values'];
        }
        break;
    }

    $state = [$options['state'] => [$options['selector'] => $values]];

    return $state;
  }

}
