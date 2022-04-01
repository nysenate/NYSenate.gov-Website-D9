<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * BlacklistFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "blacklist_field_validation_rule",
 *   label = @Translation("Words blacklist"),
 *   description = @Translation("Validates that user-entered data doesn't contain any of the specified illegal words.")
 * )
 */
class BlacklistFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
    $form['setting'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklisted words'),
      '#description' => $this->t('Specify illegal words, seperated by commas. Make sure to escape reserved regex characters with an escape (\) character.'),
      '#default_value' => $this->configuration['setting'],
      '#required' => TRUE,
    ];

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
    $setting = isset($settings['setting']) ? $settings['setting'] : '';
    $blacklist = explode(',', $setting);
    $blacklist = array_map('trim', $blacklist);
    $blacklist_regex = implode('|', $blacklist);	
	//$settings = $this->rule->settings;
	if ($value !== '' && !is_null($value) && preg_match("/$blacklist_regex/i", $value)) {
	  $context->addViolation($rule->getErrorMessage());
    }	

  }
}
