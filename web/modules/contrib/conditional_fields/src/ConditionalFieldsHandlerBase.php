<?php

namespace Drupal\conditional_fields;

/**
 * Defines a base handler implementation that most handlers plugins will extend.
 */
abstract class ConditionalFieldsHandlerBase implements ConditionalFieldsHandlersPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getWidgetValue(array $value_form) {
    if (empty($value_form)) {
      return NULL;
    }
    else {
      return $value_form[0]['value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionValues(array $options) {

    if (isset($options['values'])) {
      $value_data = $options['values'];
    }
    else {
      $value_data = $options;
    }
    if (is_array($value_data)) {
      $values = $value_data;
    }
    elseif (is_string($value_data)) {
      $values = preg_split("/[\r\n]+/g", $value_data);
    }
    else {
      $values = [];
    }
    return $values;
  }

}
