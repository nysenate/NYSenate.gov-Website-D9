<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * LengthFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "length_field_validation_rule",
 *   label = @Translation("length"),
 *   description = @Translation("Length.")
 * )
 */
class LengthFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min'),
      '#default_value' => $this->configuration['min'],
      '#required' => TRUE,
    ];
    $form['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max'),
      '#default_value' => $this->configuration['max'],
      '#required' => TRUE,
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
    if ($value != '') {
      $flag = TRUE;
      $length = mb_strlen($value, 'UTF-8');
      if (isset($settings['min']) && $settings['min'] != '') {
        //$min = token_replace($settings['min'], array($this->get_token_type() => $this->entity));
		$min = $settings['min'];
		if ($length < $min) {
          $flag = FALSE;
        }
      }
      if (isset($settings['max']) && $settings['max'] != '') {
        //$max = token_replace($settings['max'], array($this->get_token_type() => $this->entity));
		$max = $settings['max'];
		if ($length > $max) {
          $flag = FALSE;
        }
      }       

      if (!$flag) {
        //$this->set_error($token);
		$context->addViolation($rule->getErrorMessage());
      }
    }	
    //return true;
  }
}
