<?php

namespace Drupal\charts\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * @file
 * Defines Drupal\charts\Plugin\views\field\ScatterField.
 */

/**
 * Field handler to provide x and y values for a scatter plot.
 *
 * @ingroup views_field_handlers
 * @ViewsField("field_charts_fields_scatter")
 */
class ScatterField extends FieldPluginBase {

  /**
   * Sets the initial field data at zero.
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $this->field_alias = 'scatter_field';
    $options['fieldset_one']['default'] = NULL;
    $options['fieldset_two']['default'] = NULL;
    $options['fieldset_one']['x_axis'] = ['default' => NULL];
    $options['fieldset_two']['y_axis'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $fieldList = $this->displayHandler->getFieldLabels();
    unset($fieldList['field_charts_fields_scatter']);

    $form['fieldset_one'] = [
      '#type' => 'fieldset',
      '#title' => t('Select the field representing the X axis.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => -10,
      '#required' => TRUE,
    ];
    $form['fieldset_one']['x_axis'] = [
      '#type' => 'radios',
      '#title' => t('X Axis Field'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_one']['x_axis'],
      '#weight' => -10,
    ];

    $form['fieldset_two'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => t('Select the field representing the Y axis.'),
      '#weight' => -9,
      '#required' => TRUE,
    ];
    $form['fieldset_two']['y_axis'] = [
      '#type' => 'radios',
      '#title' => t('Y Axis Field'),
      '#options' => $fieldList,
      '#default_value' => $this->options['fieldset_two']['y_axis'],
      '#weight' => -9,
    ];

    return $form;
  }

  /**
   * Get the value of a simple math field.
   *
   * @param ResultRow $values
   *    Row results.
   * @param bool $xAxis
   *   Whether we are fetching field one's value.
   *
   * @return mixed
   *   The field value.
   *
   * @throws \Exception
   */
  protected function getFieldValue(ResultRow $values, $xAxis) {

    if (!empty($xAxis)) {
      $field = $this->options['fieldset_one']['x_axis'];
    }
    else {
      $field = $this->options['fieldset_two']['y_axis'];
    }

    $data = NULL;

    // Fetch the data from the database alias.
    if (isset($this->view->field[$field])) {
      if ($field == 'scatter_field') {
        $data = $this->view->field['field_charts_fields_scatter']->getValue($values);
      }
      else {
        $data = $this->view->field[$field]->getValue($values);
      }
    }

    if (!isset($data)) {
      // There's no value. Default to 0.
      $data = 0;
    }

    // Ensure the input is numeric.
    if (!is_numeric($data)) {
      \Drupal::messenger()->addError(t('Check the formatting of your 
        Scatter Field inputs: one or both of them are not numeric.'));
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function getValue(ResultRow $values, $field = NULL) {
    parent::getValue($values, $field);

    $xAxisFieldValue = $this->getFieldValue($values, TRUE);
    $yAxisFieldValue = $this->getFieldValue($values, FALSE);

    $value = json_encode([
      json_decode($xAxisFieldValue),
      json_decode($yAxisFieldValue),
    ]);

    return $value;
  }

}
