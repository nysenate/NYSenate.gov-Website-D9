<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * RegexFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "regex_field_validation_rule",
 *   label = @Translation("regex"),
 *   description = @Translation("Regular expression (Perl-Compatible).")
 * )
 */
class RegexFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
      'setting' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['setting'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('Specify the Perl-compatible regular expression pattern to validate the user input against.'),
      '#default_value' => $this->configuration['setting'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['setting'] = $form_state->getValue('setting');
  }
  
  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
	$rule = isset($params['rule']) ? $params['rule'] : null;
	$context = isset($params['context']) ? $params['context'] : null;
	$settings = array();
	if(!empty($rule) && !empty($rule->configuration)){
	  $settings = $rule->configuration;
	}
    $pattern = isset($settings['setting']) ? $settings['setting'] : '';
	//$settings = $this->rule->settings;
    if ($value != '' && (!preg_match($pattern, $value))) {
		$context->addViolation($rule->getErrorMessage());
    }	

  }
}
