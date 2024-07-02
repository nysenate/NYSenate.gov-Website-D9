<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for language select list.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_language_select",
 * )
 */
class LanguageSelect extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state         = [];
    $select_states = [];

    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $state[$options['state']][$options['selector']] = [
          'value' => $this->getWidgetValue($options['value_form']),
        ];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $select_states[$options['state']][] = [
          $options['selector'] => [
            $options['condition'] => ['xor' => $options['values']],
          ],
        ];
        $state = $select_states;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $select_states[$options['state']][] = [
          $options['selector'] => [
            $options['condition'] => ['regex' => $options['regex']],
          ],
        ];
        $state = $select_states;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $options['state'] = '!' . $options['state'];
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        foreach ($options['values'] as $value) {
          $select_states[$options['state']][] = [
            $options['selector'] => [
              $options['condition'] => empty($regex) ? [$value] : $options['value'],
            ],
          ];
        }
        $state = $select_states;
        break;
    }
    return $state;
  }

}
