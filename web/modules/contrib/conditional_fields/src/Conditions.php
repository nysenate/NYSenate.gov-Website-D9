<?php

namespace Drupal\conditional_fields;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provide conditional field's lists.
 */
class Conditions {

  use StringTranslationTrait;

  /**
   * The manages modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The construct method.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The manages modules.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Provides default options for a dependency.
   */
  public function conditionalFieldsDependencyDefaultSettings() {
    return [
      'state' => 'visible',
      'condition' => 'value',
      'grouping' => 'AND',
      'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_WIDGET,
      // !Important.
      // The param default value MUST match to schema declaration.
      // @see conditional_fields.schema.yml
      'value' => '',
      'values' => [],
      'value_form' => [],
      'effect' => 'show',
      'effect_options' => [],
      'selector' => '',
    ];
  }

  /**
   * Builds a list of supported states that may be applied to a dependent field.
   */
  public function conditionalFieldsStates() {
    $states = [
      // Supported by States API.
      'visible' => $this->t('Visible'),
      '!visible' => $this->t('Invisible'),
      '!empty' => $this->t('Filled with a value'),
      'empty' => $this->t('Emptied'),
      '!disabled' => $this->t('Enabled'),
      'disabled' => $this->t('Disabled'),
      'checked' => $this->t('Checked'),
      '!checked' => $this->t('Unchecked'),
      'required' => $this->t('Required'),
      '!required' => $this->t('Optional'),
      '!collapsed' => $this->t('Expanded'),
      'collapsed' => $this->t('Collapsed'),
      // Supported by Conditional Fields.
      'unchanged' => $this->t('Unchanged (no state)'),
      // @todo Add support to these states:
      /*
      'relevant'   => $this->t('Relevant'),
      '!relevant'  => $this->t('Irrelevant'),
      'valid'      => $this->t('Valid'),
      '!valid'     => $this->t('Invalid'),
      'touched'    => $this->t('Touched'),
      '!touched'   => $this->t('Untouched'),
      '!readonly'  => $this->t('Read/Write'),
      'readonly'   => $this->t('Read Only'),
      */
    ];

    // Allow other modules to modify the states.
    $this->moduleHandler->alter('conditionalFieldsStates', $states);

    return $states;
  }

  /**
   * Builds a list of supported effects.
   *
   * That may be applied to a dependent field
   * when it changes from visible to invisible and viceversa. The effects may
   * have options that will be passed as Javascript settings and used by
   * conditional_fields.js.
   *
   * @return array
   *   An associative array of effects.
   *   Each key is an unique name for the effect.
   *   The value is an associative array:
   *   - label: The human readable name of the effect.
   *   - states: The states that can be associated with this effect.
   *   - options: An associative array of effect options names, field types,
   *     descriptions and default values.
   */
  public function conditionalFieldsEffects() {
    $effects = [
      'show' => [
        'label' => $this->t('Show/Hide'),
        'states' => ['visible', '!visible'],
      ],
      'fade' => [
        'label' => $this->t('Fade in/Fade out'),
        'states' => ['visible', '!visible'],
        'options' => [
          'speed' => [
            '#type' => 'textfield',
            '#description' => $this->t('The speed at which the animation is performed, in milliseconds.'),
            '#default_value' => 400,
          ],
        ],
      ],
      'slide' => [
        'label' => $this->t('Slide down/Slide up'),
        'states' => ['visible', '!visible'],
        'options' => [
          'speed' => [
            '#type' => 'textfield',
            '#description' => $this->t('The speed at which the animation is performed, in milliseconds.'),
            '#default_value' => 400,
          ],
        ],
      ],
      'fill' => [
        'label' => $this->t('Fill field with a value'),
        'states' => ['!empty'],
        'options' => [
          'value' => [
            '#type' => 'textfield',
            '#description' => $this->t('The value that should be given to the field when automatically filled.'),
            '#default_value' => '',
          ],
          'reset' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Restore previous value when untriggered'),
            '#default_value' => 1,
          ],
        ],
      ],
      'empty' => [
        'label' => $this->t('Empty field'),
        'states' => ['empty'],
        'options' => [
          'value' => [
            '#type' => 'hidden',
            '#description' => $this->t('The value that should be given to the field when automatically emptied.'),
            '#value' => '',
            '#default_value' => '',
          ],
          'reset' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Restore previous value when untriggered'),
            '#default_value' => 1,
          ],
        ],
      ],
    ];

    // Allow other modules to modify the effects.
    $this->moduleHandler->alter('conditionalFieldsEffects', $effects);

    return $effects;
  }

  /**
   * List of states of a control field that may be used to evaluate a condition.
   */
  public function conditionalFieldsConditions($checkboxes = TRUE) {
    // Supported by States API.
    $conditions = [
      '!empty' => $this->t('Filled'),
      'empty' => $this->t('Empty'),
      'touched' => $this->t('Touched'),
      '!touched' => $this->t('Untouched'),
      'focused' => $this->t('Focused'),
      '!focused' => $this->t('Unfocused'),
    ];

    if ($checkboxes) {
      // Relevant only if control is a list of checkboxes.
      $conditions['checked'] = $this->t('Checked');
      $conditions['!checked'] = $this->t('Unchecked');
    }

    $conditions['value'] = $this->t('Value');

    // Allow other modules to modify the conditions.
    $this->moduleHandler
      ->alter('conditionalFieldsConditions', $conditions);

    return $conditions;
  }

}
