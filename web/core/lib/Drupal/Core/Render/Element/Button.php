<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides an action button form element.
 *
 * When the button is pressed, the form will be submitted to Drupal, where it is
 * validated and rebuilt. The submit handler is not invoked.
 *
 * Properties:
 * - #limit_validation_errors: An array of form element keys that will block
 *   form submission when validation for these elements or any child elements
 *   fails. Specify an empty array to suppress all form validation errors.
 * - #value: The text to be shown on the button.
 *
 *
 * Usage Example:
 * @code
 * $form['actions']['preview'] = array(
 *   '#type' => 'button',
 *   '#value' => $this->t('Preview'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Submit
 *
 * @FormElement("button")
 */
class Button extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#name' => 'op',
      '#is_button' => TRUE,
      '#executes_submit_callback' => FALSE,
      '#limit_validation_errors' => FALSE,
      '#process' => [
        [$class, 'processButton'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderButton'],
      ],
      '#uses_button_tag' => FALSE,
    ];
  }

  /**
   * Processes a form button element.
   */
  public static function processButton(&$element, FormStateInterface $form_state, &$complete_form) {
    // If this is a button intentionally allowing incomplete form submission
    // (e.g., a "Previous" or "Add another item" button), then also skip
    // client-side validation.
    if (isset($element['#limit_validation_errors']) && $element['#limit_validation_errors'] !== FALSE) {
      $element['#attributes']['formnovalidate'] = 'formnovalidate';
    }
    return $element;
  }

  /**
   * Prepares a #type 'button' render element for input.html.twig or button.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #attributes, #button_type, #name, #value. The
   *   #button_type property accepts any value, though core themes have CSS that
   *   styles the following button_types appropriately: 'primary', 'danger'.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig or button.html.twig.
   */
  public static function preRenderButton($element) {
    $element['#attributes']['type'] = 'submit';
    Element::setAttributes($element, ['id', 'name', 'value']);

    $element['#attributes']['class'][] = 'button';
    if (!empty($element['#button_type'])) {
      $element['#attributes']['class'][] = 'button--' . $element['#button_type'];
    }
    $element['#attributes']['class'][] = 'js-form-submit';
    $element['#attributes']['class'][] = 'form-submit';

    if (!empty($element['#attributes']['disabled'])) {
      $element['#attributes']['class'][] = 'is-disabled';
    }

    if (!isset($element['#theme_wrappers'])) {
      $element['#theme_wrappers'] = [];
    }

    // Determine whether to use input.html.twig or button.html.twig as template.
    $tag = !empty($element['#uses_button_tag']) ? 'button' : 'input';
    array_unshift($element['#theme_wrappers'], $tag . '__submit');

    return $element;
  }

}
