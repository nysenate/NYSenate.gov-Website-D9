<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * EmailFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "email_field_validation_rule",
 *   label = @Translation("Email"),
 *   description = @Translation("Email.")
 * )
 */
class EmailFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

  }

  public function validate($params) {
    $value = $params['value'] ?? '';
	$rule = $params['rule'] ?? null;
	$context = $params['context'] ?? null;

    if ($value != '' && (!\Drupal::service('email.validator')->isValid($value))) {
      $context->addViolation($rule->getErrorMessage());
    }
  }
}