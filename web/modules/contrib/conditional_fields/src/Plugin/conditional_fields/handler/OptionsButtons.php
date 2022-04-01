<?php

namespace Drupal\conditional_fields\Plugin\conditional_fields\handler;

use Drupal\conditional_fields\ConditionalFieldsHandlerBase;
use Drupal\conditional_fields\ConditionalFieldsInterface;

/**
 * Provides states handler for Check boxes/radio buttons.
 *
 * @ConditionalFieldsHandler(
 *   id = "states_handler_options_buttons",
 * )
 */
class OptionsButtons extends ConditionalFieldsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function statesHandler($field, $field_info, $options) {
    if (array_key_exists('#type', $field) && in_array($field['#type'], ['checkbox', 'checkboxes'])) {
      // Check boxes.
      return $this->checkBoxesHandler($field, $field_info, $options);
    }
    elseif (array_key_exists('#type', $field) && in_array($field['#type'], ['radio', 'radios'])) {
      // Radio.
      return $this->radioHandler($field, $field_info, $options);
    }
    return [];
  }

  /**
   * Return state for radio.
   */
  protected function radioHandler($field, $field_info, $options) {
    $select_states = [];
    $values_array  = $this->getConditionValues($options);
    $state         = [];
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        // @todo Try to get key_column automatically.
        // like here:
        // @see \Drupal\conditional_fields\Plugin\conditional_fields\handler\Select::widgetCase()
        if (isset($options['value_form'][0]['value'])) {
          $column_key = 'value';
        }
        elseif (isset($options['value_form'][0]['target_id'])) {
          $column_key = 'target_id';
        }
        else {
          break;
        }
        $select_states[$options['selector']] = [$options['condition'] => $options['value_form'][0][$column_key]];
        $state = [$options['state'] => $select_states];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        if (is_array($values_array)) {
          // Will take the first value
          // because there is no possibility to choose more with radio buttons.
          $select_states[$options['selector']] = [$options['condition'] => $values_array[0]];
        }
        else {
          $select_states[$options['selector']] = [$options['condition'] => $values_array];
        }
        $state = [$options['state'] => $select_states];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        // This just works.
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $select_states[$options['selector']] = [
          $options['condition'] => ['xor' => $values_array],
        ];
        $state = [$options['state'] => $select_states];
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $options['state'] = '!' . $options['state'];
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        if (is_array($values_array)) {
          foreach ($values_array as $value) {
            $select_states[$options['selector']][] = [
              $options['condition'] => $value,
            ];
          }
        }
        else {
          $select_states[$options['selector']] = [
            $options['condition'] => $values_array,
          ];
        }

        $state = [$options['state'] => $select_states];
        break;
    }
    return $state;
  }

  /**
   * Return state for check boxes.
   */
  protected function checkBoxesHandler($field, $field_info, $options) {
    // Checkboxes are actually different form fields, so the #states property
    // has to include a state for each checkbox.
    $checkboxes_selectors = [];
    $state                = [];
    $values_array         = $this->getConditionValues($options);
    $select               = conditional_fields_field_selector($field);
    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET:
        $values_array = $this->getWidgetValue($options['value_form']);
        // We are placed on the parent field with options.
        if (isset($field['#options'])) {
          foreach ($field['#options'] as $id => $label) {
            if (isset($field[$id]) && is_array($field[$id])) {
              $selector_key = conditional_fields_field_selector($field[$id]);
              if (!$selector_key) {
                $selector_key = sprintf("[name=\"%s\"]", $this->getFieldName($field));
              }
            }
            else {
              $selector_key = $select;
            }
            $checkboxes_selectors[$selector_key] = ['checked' => in_array($id, $values_array)];
          }
        }
        elseif (isset($field['#return_value'])) {
          // We are placed inside the option of the checkboxes.
          $selector = conditional_fields_field_selector($field);
          foreach ($options['value_form'] as $value) {
            $selector_key = str_replace($field['#return_value'], current($value), $selector);
            $checkboxes_selectors[$selector_key] = ['checked' => TRUE];
          }
        }
        $state[$options['state']] = $checkboxes_selectors;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX:
        // We interpret this as: checkboxes whose values match the regular
        // expression should be checked.
        if (isset($field['#options'])) {
          foreach ($field['#options'] as $key => $label) {
            if (preg_match('/' . $options['regex'] . '/', $key)) {
              $checkboxes_selectors = [
                conditional_fields_field_selector($field[$key]) => ['checked' => TRUE],
              ];
              $state[$options['state']][] = $checkboxes_selectors;
            }
          }
        }
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        if (!empty($values_array)) {
          foreach ($values_array as $value) {
            if (isset($field[$value])) {
              $checkboxes_selectors[conditional_fields_field_selector($field[$value])] = ['checked' => TRUE];
            }
          }
        }
        else {
          $checkboxes_selectors[conditional_fields_field_selector($field[$options['values']])] = ['checked' => TRUE];
        }
        $state[$options['state']] = $checkboxes_selectors;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        foreach ($values_array as $index => $value) {
          if ($index > 0) {
            $checkboxes_selectors[] = 'xor';
          }
          $checkboxes_selectors[] = [conditional_fields_field_selector($field[$value]) => ['checked' => TRUE]];
        }
        $state[$options['state']] = $checkboxes_selectors;
        break;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $options['state'] = '!' . $options['state'];
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        foreach ($values_array as $value) {
          $checkboxes_selectors[] = [conditional_fields_field_selector($field[$value]) => ['checked' => TRUE]];
        }
        $state[$options['state']] = $checkboxes_selectors;
        break;
    }
    return $state;
  }

  /**
   * Get field name.
   *
   * @param array $field
   *   The field object.
   *
   * @return string|false
   *   The field name
   */
  public function getFieldName(array $field) {
    $field_name = FALSE;
    if (isset($field['#name'])) {
      $field_name = $field['#name'];
    }
    elseif (isset($field['#field_name'])) {
      $field_name = $field['#field_name'];
    }
    elseif (isset($field['#array_parents']) && !empty($field['#array_parents'])) {
      $field_name = $field['#parents'][0];
    }
    elseif (isset($field['#parents']) && is_array($field['#parents'])) {
      $field_name = $field['#parents'][0];
    }
    return $field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetValue(array $value_form) {
    $values = [];
    if (empty($value_form)) {
      return $values;
    }
    else {
      foreach ($value_form as $value) {
        if (isset($value['value'])) {
          $values[] = $value['value'];
        }
        elseif (isset($value['target_id'])) {
          $values[] = $value['target_id'];
        }
        elseif (isset($value['nid'])) {
          $values[] = $value['nid'];
        }
        elseif (isset($value['vid'])) {
          $values[] = $value['vid'];
        }
        elseif (isset($value['uid'])) {
          $values[] = $value['uid'];
        }
        elseif (isset($value['fid'])) {
          $values[] = $value['fid'];
        }
        elseif (isset($value['id'])) {
          $values[] = $value['id'];
        }
        else {
          $values[] = $value;
        }
      }
      return $values;
    }
  }

}
