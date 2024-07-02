<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for single on/off checkbox.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_boolean_checkbox",
 * )
 */
class Checkbox extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   *
   * @todo Different handlers for boolean and list fields.
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    $checked = FALSE;
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $widget_value = $this->getWidgetValue($options['value_form']);
        $checked = $field['#return_value'] == $widget_value;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $checked = preg_match('/' . $options['value']['RegExp'] . '/', $field['#on_value']) ? TRUE : FALSE;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        // ANDing values of a single checkbox doesn't make sense:
        // just use the first value.
        $checked = $options['values'][0] == $field['#on_value'] ? TRUE : FALSE;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $checked = in_array($field['#on_value'], $options['values']) ? TRUE : FALSE;
        break;
    }

    $state[$options['state']][$options['selector']] = ['checked' => $checked];

    return $state;
  }

  /**
   * Get values from widget settings for plugin.
   *
   * @param array $value_form
   *   Dependency options.
   *
   * @return mixed
   *   Values for triggering events.
   */
  public function getWidgetValue(array $value_form) {
    return isset($value_form[0]['value']) ? $value_form[0]['value'] : $value_form;
  }

}
