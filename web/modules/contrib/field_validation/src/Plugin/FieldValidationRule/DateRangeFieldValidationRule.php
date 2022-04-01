<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * DateRangeFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "date_range_field_validation_rule",
 *   label = @Translation("Date range"),
 *   description = @Translation("Validates user-entered text against a specified date range.")
 * )
 */
class DateRangeFieldValidationRule extends ConfigurableFieldValidationRuleBase {

  /**
   * {@inheritdoc}
   */

  public function addFieldValidationRule(FieldValidationRuleSetInterface $field_validation_rule_set) {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'min' => NULL,
	  'max' => NULL,
	  'cycle' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['cycle'] = [
      '#title' => $this->t('Cycle of date'),
      '#description' => $this->t('Specify the cycle of date, support: global, year, month, week, day, hour, minute.'),
      '#type' => 'select',
      '#options' => [
        'global' => $this->t('Global'),
        'year' => $this->t('Year'),
        'month' => $this->t('Month'),
        'week' => $this->t('Week'),
        'day' => $this->t('Day'),
        'hour' => $this->t('Hour'),
        'minute' => $this->t('Minute'),
      ],
      '#default_value' => $this->configuration['cycle'],
    ];
    $form['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum date'),
	    '#description' => $this->t('Optionally specify the minimum date.'),
      '#default_value' => $this->configuration['min'],
    ];
    $form['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum date'),
	    '#description' => $this->t('Optionally specify the maximum date.'),
      '#default_value' => $this->configuration['max'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['min'] = $form_state->getValue('min');
	$this->configuration['max'] = $form_state->getValue('max');
	$this->configuration['cycle'] = $form_state->getValue('cycle');
  }

  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
	$rule = isset($params['rule']) ? $params['rule'] : null;
	$context = isset($params['context']) ? $params['context'] : null;
	$settings = array();
	if(!empty($rule) && !empty($rule->configuration)){
	  $settings = $rule->configuration;
	}
	//$settings = $this->rule->settings;
    if ($value !== '' && !is_null($value)) {
      $flag = FALSE;
      //$settings =  $this->rule->settings;
      $cycle = isset($settings['cycle']) ? $settings['cycle'] : '';
      // support date, datetime
      if (!is_numeric($value)) {
        $value = strtotime($value);
      }

      $date_str = date("Y-m-d H:i:s", $value);
      if ($cycle =='global') {

        if (!empty($settings['min'])) {
          //$settings['min'] = $settings['min'];
          $settings['min'] = strtotime($settings['min']);
          $settings['min'] = date("Y-m-d H:i:s", $settings['min']);
        }
        if (!empty($settings['max'])) {
          //$settings['max'] = strtr($settings['max'], $tokens);
          $settings['max'] = strtotime($settings['max']);
          $settings['max'] = date("Y-m-d H:i:s", $settings['max']);
          $str_place = 0;
        }
      }
      if ($cycle =='year') {
        $str_place = 5;
        $date_str = substr($date_str, $str_place);
      }
      elseif ($cycle =='month') {
        $str_place = 8;
        $date_str = substr($date_str, $str_place);
      }
      elseif ($cycle =='week') {
        $str_place = 10;
        $week_day = date('w', strtotime($date_str));
        $date_str = substr($date_str, $str_place);
        $date_str = $week_day . $date_str;

      }
      elseif ($cycle =='day') {
        $str_place = 11;
        $date_str = substr($date_str, $str_place);

      }
      elseif ($cycle =='hour') {
        $str_place = 14;
        $date_str = substr($date_str, $str_place);

      }
      elseif ($cycle =='minute') {
        $str_place = 17;
        $date_str = substr($date_str, $str_place);
      }

      if (!empty($settings['min'])  && $date_str <  substr($settings['min'], $str_place)) {
        $flag = TRUE;
      }
      if (!empty($settings['max'])  && $date_str > substr($settings['max'], $str_place)) {
        $flag = TRUE;
      }

      if ($flag) {
        $context->addViolation($rule->getErrorMessage());
      }

    }
    //return true;
  }
}
