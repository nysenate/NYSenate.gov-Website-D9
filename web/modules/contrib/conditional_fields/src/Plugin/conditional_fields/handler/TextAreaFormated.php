<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for textarea fields.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_text_textarea_with_summary",
 * )
 */
class TextAreaFormated extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $values_array = $this->getConditionValues($options);
    $input_states = [];
    // Text fields values are keyed by cardinality, so we have to flatten them.
    // @todo support multiple values.
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        foreach ($options['value_form'] as $value) {
          $input_states[$options['selector']] = ['value' => $value['value']];
        }
        $state[$options['state']] = $input_states;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        if (!empty($selectors)) {
          foreach ($selectors as $selector) {
            $input_states[$selector['selector']] = [
              $options['condition'] => $values_array,
            ];
            $state[$options['state']][] = $input_states;
          }
        }
        else {
          $input_states[$options['selector']] = [
            $options['condition'] => $values_array,
          ];
          $state[$options['state']] = $input_states;
        }
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $values[$options['condition']] = ['regex' => $options['regex']];
        $state[$options['state']][$options['selector']] = $values;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $input_states[$options['selector']] = [
          $options['condition'] => ['xor' => $values_array],
        ];
        $state[$options['state']] = $input_states;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $options['state'] = '!' . $options['state'];
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        if (!empty($values_array)) {
          foreach ($values_array as $value) {
            $input_states[$options['selector']][] = [
              $options['condition'] => $value,
            ];
          }
        }
        else {
          $input_states[$options['selector']] = [
            $options['condition'] => $values_array,
          ];
        }
        $state[$options['state']] = $input_states;
        break;

      default:
        break;
    }
    return $state;
  }

}
