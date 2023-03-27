<?php

namespace Drupal\conditional_fields;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Render\ElementInfoManager;

/**
 * Helper to interact with forms.
 */
class ConditionalFieldsFormHelper {

  /**
   * The form array being modified.
   *
   * @var array
   */
  public $form;

  /**
   * The state of the form being modified.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  public $form_state;

  /**
   * Array of effects for being applied to the conditional fields in this form.
   *
   * @var array
   */
  public $effects;

  /**
   * A form element information manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected $elementInfo;

  /**
   * A manager for conditional fields handlers.
   *
   * @var \Drupal\conditional_fields\ConditionalFieldsHandlersManager
   */
  protected $type;

  /**
   * ConditionalFieldsFormHelper constructor.
   *
   * @param \Drupal\Core\Render\ElementInfoManager $element_info
   *   A form element information manager.
   * @param \Drupal\conditional_fields\ConditionalFieldsHandlersManager $type
   *   A manager for conditional fields handlers.
   */
  public function __construct(ElementInfoManager $element_info, ConditionalFieldsHandlersManager $type) {
    $this->elementInfo = $element_info;
    $this->type = $type;
  }

  /**
   * An after_build callback for forms with dependencies.
   *
   * Builds and attaches #states properties to dependent fields, adds additional
   * visual effects handling to the States API and attaches a validation
   * callback to the form that handles validation of dependent fields.
   *
   * @param array $form
   *   The form array being modified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form being modified.
   *
   * @return array
   *   The modified form array.
   */
  public function afterBuild(array $form, FormStateInterface &$form_state) {
    $this->form = $form;
    $this->form_state = $form_state;

    if ($this->hasConditionalFields()) {
      $this->processDependentFields()
        ->addJavascriptEffects()
        ->addValidationCallback();
    }

    return $this->form;
  }

  /**
   * Build and attach #states properties to dependent fields.
   */
  public function processDependentFields() {
    $this->effects = [];

    // Cycle all dependents.
    foreach ($this->form['#conditional_fields'] as $dependent => $dependent_info) {
      $states = [];

      if (!isset($dependent_info['dependees']) || empty($dependent_info['dependees'])) {
        continue;
      }
      $dependees = $dependent_info['dependees'];

      $dependent_location = array_merge([], [$dependent]);
      $dependent_form_field = NestedArray::getValue($this->form, $dependent_location);

      $states = $this->processDependeeFields($dependees, $dependent_form_field, $dependent_location, $states);

      if (empty($states)) {
        continue;
      }

      // If the state is "checked" or "not checked", and the dependent form
      // field is a container (i.e.: the form field wrapper around a checkbox or
      // radio button), and there is a checkbox or radio button deeper in the
      // array, we actually need to use the (deeper) checkbox or radio button
      // because it is not possible to set a checked state on a container.
      if (count(array_intersect(['checked', '!checked'], array_keys($states))) > 0
        && $dependent_form_field['#type'] === 'container'
        && isset($dependent_form_field['widget']['value']['#type'])
        && in_array($dependent_form_field['widget']['value']['#type'], ['checkbox', 'radio'])
      ) {
        $dependent_location = array_merge($dependent_location, ['widget', 'value']);
        $dependent_form_field = $dependent_form_field['widget']['value'];
      }

      // Save the modified field back into the form.
      NestedArray::setValue($this->form, $dependent_location, $dependent_form_field);

      // Add the #states property to the dependent field.
      NestedArray::setValue($this->form, array_merge($dependent_location, ['#states']), $this->mapStates($states));
    }

    return $this;
  }

  /**
   * Determine and register dependee field effects.
   */
  public function processDependeeFields($dependees, &$dependent_form_field = [], $dependent_location = [], $states = []) {
    // Cycle the dependant's dependees.
    foreach ($dependees as $dependency) {
      $dependee = $dependency['dependee'];

      if (empty($this->form['#conditional_fields'][$dependee])) {
        continue;
      }

      $dependee_info = $this->form['#conditional_fields'][$dependee];

      end($dependee_info['parents']);
      $unset = key($dependee_info['parents']);
      if (is_int($dependee_info['parents'][$unset]) && $dependee_info['parents'][$unset] > 0) {
        unset($dependee_info['parents'][$unset]);
      }

      if (isset($this->form[$dependee]['#attributes'])
        && $this->form[$dependee]['#attributes']['class'][0] == 'field--type-list-string'
        && $this->form[$dependee]['widget']['#type'] == 'checkboxes') {
        array_pop($dependee_info['parents']);
      }

      $dependee_form_field = NestedArray::getValue($this->form, $dependee_info['parents']);
      $options = $dependency['options'];

      // Apply reset dependent to default if untriggered behavior.
      if (!empty($options['reset'])) {
        // Add property to element so conditional_fields_dependent_validate()
        // can pick it up.
        $dependent_form_field['#conditional_fields_reset_if_untriggered'] = TRUE;
      }

      if (!empty($options['values']) && is_string($options['values'])) {
        $options['values'] = explode("\r\n", $options['values']);
      }

      $options['selector'] = $this->getSelector($options['selector'], $dependee_form_field);

      $state = $this->getState($dependee, $dependee_form_field, $options);

      // Add validation callback to element if the dependency can be evaluated.
      if (in_array($options['condition'], [
        'value',
        'empty',
        '!empty',
        'checked',
        '!checked',
      ])) {
        $dependent_form_field = $this->elementAddProperty($dependent_form_field,
          '#element_validate',
          [self::class, 'dependentValidate'],
          'append');
      }

      $states = $this->addStateToGroup($state, $options, $states);

      $selector = $this->buildJquerySelectorForField(NestedArray::getValue($this->form, [$dependent_location[0]]));
      $this->effects[$selector] = $this->getEffect($options);
    }

    return $states;
  }

  /**
   * Add our Javascript and effects.
   */
  public function addJavascriptEffects() {
    $this->form['#attached']['library'][] = 'conditional_fields/conditional_fields';
    // Add effect settings to the form.
    if ($this->effects) {
      $this->form['#attached']['drupalSettings']['conditionalFields'] = [
        'effects' => $this->effects,
      ];
    }

    return $this;
  }

  /**
   * Add validation callback to manage dependent fields validation.
   */
  public function addValidationCallback() {
    $this->form['#validate'][] = [self::class, 'formValidate'];
    // Initialize validation information every time the form is rendered to
    // avoid stale data after a failed submission.
    $this->form_state->setValue('conditional_fields_untriggered_dependents', []);

    return $this;
  }

  /**
   * Get list of states for the pair from the options.
   *
   * @param string $dependee
   *   Machine name of control field.
   * @param array $dependee_form_field
   *   Nested array of control field.
   * @param array $options
   *   Settings of dependency.
   *
   * @return array
   *   List of states.
   *
   * @see hook_get_state
   */
  protected function getState($dependee, array $dependee_form_field, array $options) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->form_state->getFormObject()->getFormDisplay($this->form_state);
    $state = [];

    if ($options['condition'] != 'value') {
      // Conditions different than "value" are always evaluated against TRUE.
      $state = [$options['state'] => [$options['selector'] => [$options['condition'] => TRUE]]];
    }
    else {
      $field_name = explode('[', $dependee_form_field['#name']);
      $dependee_form_state = isset($dependee_form_field['#field_parents'], $field_name[0], $this->form_state) ? WidgetBase::getWidgetState($dependee_form_field['#field_parents'], $field_name[0], $this->form_state) : NULL;
      $dependee_form_field['#original_name'] = $field_name[0];
      $dependee_display = $form_display->getComponent($dependee);
      if (is_array($dependee_display) && array_key_exists('type', $dependee_display)) {
        $widget_id = $dependee_display['type'];
      }

      // @todo Use field cardinality instead of number of values that was
      // selected on manage dependency tab. As temporary solution put
      // cardinality in $options. Format of #states depend on field widget and
      // field cardinality (it can be like value: string and value: [array]).
      if ($field_config = FieldStorageConfig::loadByName($form_display->getTargetEntityTypeId(), $dependee)) {
        $options['field_cardinality'] = $field_config->getCardinality();
      }

      // Execute special handler for fields that need further processing.
      // The handler has no return value. Modify the $state parameter by
      // reference if needed.
      if (isset($widget_id)) {
        $handler_id = 'states_handler_' . $widget_id;
        /** @var Drupal\conditional_fields\ConditionalFieldsHandlersPluginInterface $handler */
        $handler = $this->type->createInstance($handler_id);
        $state = $handler->statesHandler($dependee_form_field, $dependee_form_state, $options);
      }

      if (empty($state)) {
        // If states empty Default plugin.
        /** @var Drupal\conditional_fields\ConditionalFieldsHandlersPluginInterface $default_handler */
        $default_handler = $this->type->createInstance('states_handler_default_state');
        $state = $default_handler->statesHandler($dependee_form_field, $dependee_form_state, $options);
      }
    }

    return $state;
  }

  /**
   * Determine whether the form has conditional fields.
   */
  public function hasConditionalFields() {
    // Dependencies data is attached in
    // conditional_fields_element_after_build().
    if (empty($this->form['#conditional_fields'])) {
      return FALSE;
    }
    if (!method_exists($this->form_state->getFormObject(), 'getFormDisplay')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Build a jQuery selector if it was not overridden by a custom value.
   *
   * Note that this may be overridden later by a state handler.
   */
  public function getSelector($options_selector, $dependee_form_field) {
    if (!$options_selector) {
      $selector = $this->buildJquerySelectorForField($dependee_form_field);
    }
    else {
      // Replace the language placeholder in the selector with current language.
      $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $language = isset($dependee_form_field['#language']) ? $dependee_form_field['#language'] : $current_language;
      $selector = str_replace('%lang', $language, $options_selector);
    }
    return $selector;
  }

  /**
   * Merge field states to general list.
   *
   * @param array $new_states
   *   List of new states to add to the existing states.
   * @param array $options
   *   Field CF settings.
   * @param array $states
   *   An array of existing states.
   *
   * @return array
   *   An array of modified states.
   */
  protected function addStateToGroup(array $new_states, array $options, array $states) {
    // Add the $state into the correct logic group in $states.
    foreach ($new_states as $key => $constraints) {
      if (empty($states[$key][$options['grouping']])) {
        $states[$key][$options['grouping']] = $constraints;
      }
      else {
        $states[$key][$options['grouping']] = array_merge($states[$key][$options['grouping']], $constraints);
      }
    }

    return $states;
  }

  /**
   * Returns js effect for field.
   *
   * @param array $options
   *   Field CF settings.
   *
   * @return array
   *   Effect with options.
   */
  public function getEffect(array $options) {
    // Build effect settings for effects with options.
    // @todo add dependee key to allow different effects on the same selector.
    if ($options['effect'] && $options['effect'] != 'show') {
      // Convert numeric strings to numbers.
      foreach ($options['effect_options'] as &$effect_option) {
        if (is_numeric($effect_option)) {
          $effect_option += 0;
        }
      }
      return [
        'effect' => $options['effect'],
        'options' => $options['effect_options'],
      ];
    }
    return [];
  }

  /**
   * Dependent field validation callback.
   *
   * If the dependencies of a dependent field are not triggered, the validation
   * errors that it might have thrown must be removed, together with its
   * submitted values. This will simulate the field not being present in the
   * form at all. In this field-level callback we just collect needed
   * information and store it in $form_state. Values and errors will be removed
   * in a single sweep in formValidate(), which runs at the end of the
   * validation cycle.
   *
   * @see \Drupal\conditional_fields\ConditionalFieldsFormHelper::formValidate()
   */
  public static function dependentValidate($element, FormStateInterface &$form_state, $form) {
    if (!isset($form['#conditional_fields'])) {
      return;
    }

    $dependent = $form['#conditional_fields'][reset($element['#array_parents'])];

    // Check if this field's dependencies were triggered.
    $triggered = self::evaluateDependencies($dependent, $form, $form_state);
    $return = FALSE;

    // @todo Refactor this!
    if ($evaluated_dependencies = self::evaluateDependencies($dependent, $form, $form_state, FALSE)) {
      foreach ($evaluated_dependencies[reset($dependent['field_parents'])] as $operator) {
        foreach ($operator as $state => $result) {
          if (($result && $state == 'visible' && $triggered) || (!$result && $state == '!visible' && !$triggered)) {
            $return = TRUE;
          }
          if (($result && $state == 'required' && $triggered) || (!$result && $state == '!required' && !$triggered)) {
            $return = TRUE;
            $key_exists = NULL;
            $input_state = NestedArray::getValue($form_state->getValues(), $dependent['field_parents'], $key_exists);
            if ($key_exists && !is_object($input_state) && isset($input_state['add_more'])) {
              // Remove the 'value' of the 'add more' button.
              unset($input_state['add_more']);
            }
            $input_state = (is_null($input_state)) ? [] : $input_state;
            if (isset($dependent['field_parents'][0])) {
              $field = FieldStorageConfig::loadByName($form['#entity_type'], $dependent['field_parents'][0]);
            }
            else {
              $field = NULL;
            }
            if (empty($input_state)) {
              if (isset($element['widget']['#title'])) {
                $title = $element['widget']['#title'];
              }
              elseif (isset($dependent['field_parents'][0])) {
                $title = $dependent['field_parents'][0];
              }
              elseif ($field) {
                $title = $field->getName();
              }

              $form_state->setError($element, t('%name is required.', ['%name' => $title]));
            }
          }
        }
      }
    }

    if ($return) {
      return;
    }

    // Mark submitted values for removal. We have to remove them after all
    // fields have been validated to avoid collision between dependencies.
    $form_state_addition['parents'] = $dependent['field_parents'];

    // Optional behavior: reset the field to its default values.
    // Default values are always valid, so it's safe to skip validation.
    if (!empty($element['#conditional_fields_reset_if_untriggered']) && !$triggered) {
      $form_state_addition['reset'] = TRUE;
    }
    else {
      $form_state_addition['reset'] = FALSE;
    }

    // Tag validation errors previously set on this field for removal in
    // ConditionalFieldsFormHelper::formValidate().
    $errors = $form_state->getErrors();

    if ($errors) {
      $error_key = reset($dependent['field_parents']);
      foreach ($errors as $name => $error) {
        // An error triggered by this field might have been set on a descendant
        // element. This also means that so there can be multiple errors on the
        // same field (even though Drupal doesn't support multiple errors on the
        // same element).
        if (strpos((string) $name, $error_key) === 0) {
          $field_errors[$name] = $error;
        }
      }
    }

    if (!empty($field_errors)) {
      $form_state_addition['errors'] = $field_errors;
      return;
    }

    $fiel_state_values_count = count($form_state->getValue('conditional_fields_untriggered_dependents'));
    $form_state->setValue([
      'conditional_fields_untriggered_dependents',
      $fiel_state_values_count,
    ], $form_state_addition);
  }

  /**
   * Evaluate a set of dependencies for a dependent field.
   *
   * @param array $dependent
   *   The field form element in the current language.
   * @param array $form
   *   The form to evaluate dependencies on.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form to evaluate dependencies on.
   * @param bool $grouping
   *   TRUE to evaluate grouping; FALSE otherwise.
   *
   * @return array|bool
   *   Evaluated dependencies array.
   */
  protected static function evaluateDependencies(array $dependent, array $form, FormStateInterface $form_state, $grouping = TRUE) {
    $dependencies = $form['#conditional_fields'][reset($dependent['field_parents'])]['dependees'];
    $evaluated_dependees = [];

    foreach ($dependencies as $dependency) {
      // Extract field values from submitted values.
      $dependee = $dependency['dependee'];

      // Skip any misconfigured conditions.
      if (empty($form['#conditional_fields'][$dependee]['parents'])) {
        continue;
      }

      $dependee_parents = $form['#conditional_fields'][$dependee]['parents'];

      // We have the parents of the field, but depending on the entity type and
      // the widget type, they may include additional elements that are actually
      // part of the value. So we find the depth of the field inside the form
      // structure and use the parents only up to that depth.
      $dependee_parents_keys = array_flip($dependee_parents);
      $dependee_parent = NestedArray::getValue($form, array_slice($dependee_parents, 0, $dependee_parents_keys[$dependee]));
      $values = self::formFieldGetValues($dependee_parent[$dependee], $form_state);
      if (isset($values['value']) && is_numeric($values['value'])) {
        $values = $values['value'];
      }
      // Remove the language key.
      if (isset($dependee_parent[$dependee]['#language'], $values[$dependee_parent[$dependee]['#language']])) {
        $values = $values[$dependee_parent[$dependee]['#language']];
      }

      if ($grouping) {
        $evaluated_dependees[reset($dependent['field_parents'])][$dependency['options']['grouping']][] = self::evaluateDependency('edit', $values, $dependency['options']);
      }
      else {
        $evaluated_dependees[reset($dependent['field_parents'])][$dependency['options']['grouping']][$dependency['options']['state']] = self::evaluateDependency('edit', $values, $dependency['options']);
      }
    }

    if ($grouping) {
      return self::evaluateGrouping($evaluated_dependees[reset($dependent['field_parents'])]);
    }

    return $evaluated_dependees;
  }

  /**
   * Evaluates an array with 'AND', 'OR' and 'XOR' groupings.
   *
   * Each containing a list of boolean values.
   */
  protected static function evaluateGrouping($groups) {
    $or = $and = $xor = TRUE;
    if (!empty($groups['OR'])) {
      $or = in_array(TRUE, $groups['OR']);
    }
    if (!empty($groups['AND'])) {
      $and = !in_array(FALSE, $groups['AND']);
    }
    if (!empty($groups['XOR'])) {
      $xor = array_sum($groups['XOR']) == 1;
    }
    return $or && $and && $xor;
  }

  /**
   * Validation callback for any form with conditional fields.
   *
   * This validation callback is added to all forms that contain fields with
   * dependencies. It removes all validation errors from dependent fields whose
   * dependencies are not triggered, which were collected at field-level
   * validation in ConditionalFieldsFormHelper::dependentValidate().
   *
   * @see \Drupal\conditional_fields\ConditionalFieldsFormHelper::dependentValidate()
   */
  public static function formValidate($form, FormStateInterface &$form_state) {
    if (empty($form_state->getValue('conditional_fields_untriggered_dependents'))) {
      return;
    }

    $entity = $form_state->getFormObject()->getEntity();
    $untriggered_dependents_errors = [];

    foreach ($form_state->getValue('conditional_fields_untriggered_dependents') as $field) {
      $parent = [$field['parents'][0]];
      $dependent = NestedArray::getValue($form, $parent);
      $field_values_location = self::formFieldGetValues($dependent, $form_state);

      $dependent_field_name = reset($dependent['#array_parents']);

      // If we couldn't find a location for the field's submitted values, let
      // the validation errors pass through to avoid security holes.
      if (empty($field_values_location)) {
        if (!empty($field['errors'])) {
          $untriggered_dependents_errors = array_merge($untriggered_dependents_errors, $field['errors']);
        }
        continue;
      }

      // Save the changed array back in place.
      // Do not use form_set_value() since it assumes
      // that the values are located at
      // $form_state['values'][ ... $element['#parents'] ... ], while the
      // documentation of hook_field_widget_form() states that field values are
      // // $form_state['values'][ ... $element['#field_parents'] ... ].
      // NestedArray::setValue($form_state['values'], $dependent['#field_parents'], $field_values_location);
      if (!empty($field['reset'])) {
        $default = $entity->getFieldDefinition($dependent_field_name)->getDefaultValue($entity);
        // Save the changed array back in place.
        $form_state->setValue($dependent_field_name, $default);
      }

      if (!empty($field['errors'])) {
        $untriggered_dependents_errors = array_merge($untriggered_dependents_errors, $field['errors']);
      }
    }

    if (!empty($untriggered_dependents_errors)) {
      // Since Drupal provides no clean way to selectively remove error
      // messages, we have to store all current form errors and error messages,
      // clear them, filter out from our stored values the errors originating
      // from untriggered dependent fields, and then reinstate remaining errors
      // and messages.
      $errors = array_diff_assoc((array) $form_state->getErrors(), $untriggered_dependents_errors);
      $form_state->clearErrors();
      $error_messages = \Drupal::messenger()->messagesByType('error');
      $removed_messages = array_values($untriggered_dependents_errors);

      // Reinstate remaining errors.
      foreach ($errors as $name => $error) {
        $form_state->setErrorByName($name, $error);
        // form_set_error() calls drupal_set_message(), so we have to filter out
        // these from the messages to avoid duplicates.
        $removed_messages[] = $error;
      }

      // Reinstate remaining error messages (which, at this point,
      // are messages that were originated outside of the validation process).
      if (!empty($error_messages['error'])) {
        $error_messages_array = $error_messages['error'] instanceof MarkupInterface ? $error_messages['error']->jsonSerialize() : $error_messages['error'];
        foreach (array_diff($error_messages_array, $removed_messages) as $message) {
          \Drupal::messenger()->addMessage($message, 'error');
        }
      }
    }
  }

  /**
   * Extracts submitted field values during form validation.
   *
   * @return array|null
   *   The requested field values parent. Actual field vales are stored under
   *   the key $element['#field_name'].
   */
  protected static function formFieldGetValues($element, FormStateInterface $form_state) {
    // Fall back to #parents to support custom dependencies.
    $parents = !empty($element['#array_parents']) ? $element['#array_parents'] : $element['#parents'];
    return NestedArray::getValue($form_state->getValues(), $parents);
  }

  /**
   * Evaluate if a dependency meets the requirements to be triggered.
   *
   * @param string $context
   *   Options:
   *   'edit' if $values are extracted from $form_state.
   *   'view' if $values are extracted from an entity.
   * @param mixed $values
   *   An array of values to evaluate the dependency based on.
   * @param array $options
   *   An array of options.
   *
   * @return bool
   *   Can the dependency be triggered?
   */
  protected static function evaluateDependency($context, $values, array $options) {
    if ($options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET) {
      $dependency_values = $context == 'view' ? $options['value'] : $options['value_form'];

      if ($options['condition'] === '!empty') {
        $values = (isset($values[0]['value'])) ? $values[0]['value'] : $values;
        $values = ($values === '_none') ? '' : $values;
        return (!empty($values)) ? TRUE : FALSE;
      }

      if ($options['condition'] === 'empty') {
        $values = (isset($values[0]['value'])) ? $values[0]['value'] : $values;
        $values = ($values === '_none') ? '' : $values;
        return (empty($values)) ? TRUE : FALSE;
      }

      // The BooleanList widget provides an empty array as $dependency_values,
      // thus checking this field requires a different handling in case of
      // 'checked or '!checked' conditions, where $value has 0 or 1.
      if ($options['condition'] === 'checked' || $options['condition'] === '!checked') {
        $dependency_values = (int) ($options['condition'] === 'checked');
      }

      // Simple case: both values are strings or integers. Should never happen
      // in view context, but does no harm to check anyway.
      if (!is_array($values) || (is_array($values) && empty($values))) {
        // Options elements consider "_none" value same as empty.
        $values = $values === '_none' ? '' : $values;

        if (!is_array($dependency_values)) {
          // Some widgets store integers, but values saved in $dependency_values
          // are always strings. Convert them to integers because we want to do
          // a strict equality check to differentiate empty strings from '0'.
          if (is_int($values) && is_numeric($dependency_values)) {
            $dependency_values = (int) $dependency_values;
          }
          return $dependency_values === $values;
        }

        // If $values is a string and $dependency_values an array, convert
        // $values to the standard field array form format. This covers cases
        // like single value textfields.
        $values = [['value' => $values]];
      }

      // If we are in form context, we are almost done.
      if ($context == 'edit') {
        // If $dependency_values is not an array, we can only assume that it
        // should map to the first key of the first value of $values.
        if (!is_array($dependency_values)) {
          if (is_null(current($values)) || empty($options['value'][0])) {
            return FALSE;
          }
          $key = current(array_keys((array) current($values)));
          $dependency_values = [[$key => $options['value'][0][$key]]];
          $temp[][$key] = $values[0][$key];
          $values = $temp;
        }

        // Compare arrays recursively ignoring keys, since multiple select
        // widgets values have numeric keys in form format and string keys in
        // storage format.
        return array_values($dependency_values) == array_values($values);
      }

      // $values, when viewing fields, may contain all sort of additional
      // information, so filter out from $values the keys that are not present
      // in $dependency_values.
      // Values here are alway keyed by delta (regardless of multiple value
      // settings).
      foreach ($values as $delta => &$value) {
        if (isset($dependency_values[$delta])) {
          $value = array_intersect_key($value, $dependency_values[$delta]);

          foreach ($value as $key => &$element_value) {
            if (isset($dependency_values[$delta][$key]) && is_int($dependency_values[$delta][$key]) && is_numeric($element_value)) {
              $element_value = (int) $element_value;
            }
          }
        }
      }

      // Compare values.
      foreach ($dependency_values as $delta => $dependency_value) {
        if (!isset($values[$delta])) {
          return FALSE;
        }
        foreach ($dependency_value as $key => $dependency_element_value) {
          // Ignore keys set in the field and not in the dependency.
          if (isset($values[$delta][$key]) && $values[$delta][$key] !== $dependency_element_value) {
            return FALSE;
          }
        }
      }

      return TRUE;
    }

    // Flatten array of values.
    $reference_values = [];
    foreach ((array) $values as $value) {
      // @todo support multiple values.
      $reference_values[] = is_array($value) ? array_shift($value) : $value;
    }

    // Regular expression method.
    if ($options['values_set'] == ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX) {
      foreach ($reference_values as $reference_value) {
        if (!preg_match('/' . $options['regex'] . '/', $reference_value)) {
          return FALSE;
        }
      }
      return TRUE;
    }

    if (!empty($options['values']) && is_string($options['values'])) {
      $options['values'] = explode("\r\n", $options['values']);
    }

    switch ($options['values_set']) {
      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_AND:
        $diff = array_diff($options['values'], $reference_values);
        return empty($diff);

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_OR:
        $intersect = array_intersect($options['values'], $reference_values);
        return !empty($intersect);

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_XOR:
        $intersect = array_intersect($options['values'], $reference_values);
        return count($intersect) == 1;

      case ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_NOT:
        $intersect = array_intersect($options['values'], $reference_values);
        return empty($intersect);
    }

    return TRUE;
  }

  /**
   * Helper function to add a property/value pair to a render array.
   *
   * Safely without overriding any pre-existing value.
   *
   * @param array $element
   *   The form element that we're adding a property to.
   * @param string $property
   *   The property to add.
   * @param mixed $value
   *   The value for the property to add.
   * @param string $position
   *   Use 'append' if $value should be inserted at the end of the
   *   $element[$property] array, any other value to insert it at the beginning.
   *
   * @return array
   *   The modified element.
   */
  public function elementAddProperty(array $element, $property, $value, $position = 'prepend') {
    // Avoid overriding default element properties that might not yet be set.
    if (!isset($element[$property])) {
      $element[$property] = isset($element['#type']) ? $this->elementInfo->getInfoProperty($element['#type'], $property, []) : [];
    }
    if (is_array($value)) {
      // A method callback, wrap it around.
      $value = [$value];
    }
    if (in_array($value, $element[$property])) {
      return $element;
    }
    switch ($position) {
      case 'append':
        $element[$property] = array_merge($element[$property], (array) $value);
        break;

      case 'prepend':
      default:
        $element[$property] = array_merge((array) $value, $element[$property]);
        break;
    }

    return $element;
  }

  /**
   * Map the states based on the conjunctions.
   *
   * @param array $unmapped_states
   *   An array of unmapped states.
   *
   * @return array
   *   An array of mapped states.
   */
  public function mapStates(array $unmapped_states) {
    $states_new = [];
    foreach ($unmapped_states as $state_key => $value) {
      // As the main object is ANDed together we can add the AND items directly.
      if (!empty($unmapped_states[$state_key]['AND'])) {
        $states_new[$state_key] = $unmapped_states[$state_key]['AND'];
      }
      // The OR and XOR groups are moved into a sub-array that has numeric keys
      // so that we get a JSON array and not an object, as required by the
      // States API for OR and XOR groupings.
      if (!empty($unmapped_states[$state_key]['OR'])) {
        $or = [];
        foreach ($unmapped_states[$state_key]['OR'] as $constraint_key => $constraint_value) {
          $or[] = [$constraint_key => $constraint_value];
        }
        // '1' as a string so that we get an object (which means logic groups
        // are ANDed together).
        $states_new[$state_key]['1'] = $or;
      }
      if (!empty($unmapped_states[$state_key]['XOR'])) {
        $xor = ['xor'];
        foreach ($unmapped_states[$state_key]['XOR'] as $constraint_key => $constraint_value) {
          $xor[] = [$constraint_key => $constraint_value];
        }
        // '2' as a string so that we get an object.
        $states_new[$state_key]['2'] = $xor;
      }
    }
    return $states_new;
  }

  /**
   * Build a jQuery selector for a field, from its name or ID attribute.
   *
   * A mockable wrapper around conditional_fields_field_selector().
   *
   * @param array $field
   *   A form array for the field we want to build a selector for.
   *
   * @return false|string
   *   A jQuery selector; or FALSE if one cannot be determined.
   */
  public function buildJquerySelectorForField(array $field) {
    return conditional_fields_field_selector($field);
  }

}
