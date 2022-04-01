<?php

namespace Drupal\field_validation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FieldValidation constraint.
 */
class FieldValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $ruleset_name = $constraint->ruleset_name;
	$rule_uuid = $constraint->rule_uuid;
	$ruleset = \Drupal::entityTypeManager()->getStorage('field_validation_rule_set')->load($ruleset_name);
	if(empty($ruleset)){
	  return;
	}
	//for base field validation, we limit it to attached bundle.
    $entity = $items->getEntity();
    $bundle = $entity->bundle();
    if($bundle != $ruleset->getAttachedBundle()){
      return;	
    }
    	
	//$rule = $ruleset->getFieldValidationRule($rule_uuid);
	$rules = $ruleset->getFieldValidationRules();
	$rules_available = [];
	$field_name = $items->getFieldDefinition()->getName();
	
	//drupal_set_message($field_name);
	foreach($rules as $rule){
	  if($rule->getFieldName() == $field_name){
	    $rules_available[] = $rule;
	  }

	}
	if(empty($rules_available)){
	  return;
	}

	$params = [];
	$params['items'] = $items;
	//$params['rule'] = $rule;
	$params['context'] = $this->context;
    if ($items->count() !== 0) {	
	  foreach($items as $delta => $item){
	    $validator_manager = \Drupal::service('plugin.manager.field_validation.field_validation_rule');
        // You can hard code configuration or you load from settings.
	    foreach($rules_available as $rule) {
          $column = $rule->getColumn();
          $value = $item->{$column};
          $params['value'] = $value;
          $params['delta'] = $delta;
          $config = [];
          $params['rule'] = $rule;
          $plugin_validator = $validator_manager->createInstance($rule->getPluginId(), $config);
          $plugin_validator->validate($params);
	    }
	  }
    }else {
      $validator_manager = \Drupal::service('plugin.manager.field_validation.field_validation_rule');
      // You can hard code configuration or you load from settings.
      foreach ($rules_available as $rule) {
        $params['value'] = NULL;
        $params['delta'] = NULL;
        $config = [];
        $params['rule'] = $rule;
        $params['ruleset'] = $ruleset;
        $plugin_validator = $validator_manager->createInstance($rule->getPluginId(), $config);
        $plugin_validator->validate($params);
      }
    }
  }
}
