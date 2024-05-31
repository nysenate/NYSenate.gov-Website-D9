<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides states handler for date lists.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_datetime_datelist",
 * )
 */
class DateList extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    $state = [];
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $value = $this->getWidgetValue($options['value_form']);
        if (!$value) {
          $value = 'now';
        }
        $date_data = $this->getDateArray($value);
        if (isset($field['#value']) && is_array($field['#value'])) {
          foreach ($field['#value'] as $key => $default_value) {
            if (isset($field[$key]) && isset($date_data[$key])) {
              $selector = conditional_fields_field_selector($field[$key]);
              $state[$options['state']][$selector]['value'] = $date_data[$key];
            }
          }
        }
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        $pattern = "/\\\?[\/]+|[-\.\,\s]+/";
        $parts = preg_split($pattern, $options['regex']);
        if (is_array($parts)) {
          $date_patterns = [
            'year' => isset($parts[0]) ? $parts[0] : '.*',
            'month' => isset($parts[1]) ? $parts[1] : '.*',
            'day' => isset($parts[2]) ? $parts[2] : '.*',
            'hour' => isset($parts[3]) ? $parts[3] : '.*',
            'minute' => isset($parts[4]) ? $parts[4] : '.*',
            'second' => isset($parts[5]) ? $parts[5] : '.*',
          ];
          if (isset($field['#value']) && is_array($field['#value'])) {
            foreach ($field['#value'] as $key => $default_value) {
              if (isset($field[$key]) && isset($date_patterns[$key])) {
                $selector = conditional_fields_field_selector($field[$key]);
                $state[$options['state']][$selector]['value'] = ['regex' => $date_patterns[$key]];
              }
            }
          }
        }
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        $values_array = $this->getConditionValues($options);
        foreach ($values_array as $index => $value) {
          $values_data = [];
          $date_data = $this->getDateArray($value);
          if (isset($field['#value']) && is_array($field['#value'])) {
            foreach ($field['#value'] as $key => $default_value) {
              if (isset($field[$key]) && isset($date_data[$key])) {
                $selector = conditional_fields_field_selector($field[$key]);
                $values_data[$selector]['value'] = $date_data[$key];
              }
            }
          }
          $state[$options['state']][$index . '_'] = $values_data;
        }
        break;

      default:
        if ($options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR) {
          $separate_condition = 'xor';
        }
        elseif ($options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT) {
          $options['state'] = '!' . $options['state'];
        }
        $values_array = $this->getConditionValues($options);
        foreach ($values_array as $index => $value) {
          $values_data = [];
          $date_data = $this->getDateArray($value);
          if (isset($field['#value']) && is_array($field['#value'])) {
            foreach ($field['#value'] as $key => $default_value) {
              if (isset($field[$key]) && isset($date_data[$key])) {
                $selector = conditional_fields_field_selector($field[$key]);
                $values_data[$selector]['value'] = $date_data[$key];
              }
            }
          }
          if ($index > 0 && isset($separate_condition)) {
            $state[$options['state']][] = $separate_condition;
          }
          $state[$options['state']][] = $values_data;
        }
        break;
    }
    return $state;
  }

  /**
   * Get the dateTime object form string.
   *
   * @param string $value
   *   The value to parse as a DrupalDateTime.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The parsed DrupalDateTime.
   */
  public function getDateArray($value) {
    $date = new DrupalDateTime($value);
    if ($date->hasErrors()) {
      $pattern = "/\\\?[\/]+|[-\.\,\s]+/";
      $parts = preg_split($pattern, $value);
      $data_data = [
        'year' => isset($parts[0]) ? (int) $parts[0] : 1900,
        'month' => isset($parts[1]) ? (int) $parts[1] : 1,
        'day' => isset($parts[2]) ? (int) $parts[2] : 1,
        'hour' => isset($parts[3]) ? (int) $parts[3] : 0,
        'minute' => isset($parts[4]) ? (int) $parts[4] : 0,
        'second' => isset($parts[5]) ? (int) $parts[5] : 0,
      ];
    }
    else {
      $data_data = [
        'year' => (int) $date->format("Y"),
        'month' => (int) $date->format("n"),
        'day' => (int) $date->format("j"),
        'hour' => (int) $date->format("G"),
        'minute' => (int) $date->format("i"),
        'second' => (int) $date->format("s"),
      ];
    }
    return $data_data;
  }

}
