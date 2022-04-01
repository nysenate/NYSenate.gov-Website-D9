<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * WordsFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "words_field_validation_rule",
 *   label = @Translation("Number of words"),
 *   description = @Translation("Verifies the number of words of user-entered values, with the option to specify minimum and maximum number of words.")
 * )
 */
class WordsFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
      'max' => NULL
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
 
    $form['min'] = [
      '#type' => 'textfield',
      '#title' => 'Minimum number of words',
      '#default_value' => $this->configuration['min'],
      '#required' => TRUE,
    ];
    
    $form['max'] = [
      '#type' => 'textfield',
      '#title' => 'Maximum number of words',
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
    if ($value != '') {
      $flag = TRUE;
      $length =count(explode(' ', trim(preg_replace('/\s+/', ' ', str_replace('&nbsp;', ' ', (strip_tags(str_replace('<', ' <', $value))))))));
      if (isset($settings['min']) && $settings['min'] != '') {   
		$min = $settings['min'];
		if ($length < $min) {
          $flag = FALSE;
        }
      }
      if (isset($settings['max']) && $settings['max'] != '') {
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
