<?php

namespace Drupal\field_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_validation\ConfigurableFieldValidationRuleInterface;
use Drupal\field_validation\FieldValidationRuleSetInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for FieldValidationRule.
 */
abstract class FieldValidationRuleFormBase extends FormBase {

  /**
   * The fieldValidationRuleSet.
   *
   * @var \Drupal\field_validation\FieldValidationRuleSetInterface
   */
  protected $fieldValidationRuleSet;

  /**
   * The fieldValidationRule.
   *
   * @var \Drupal\field_validation\FieldValidationRuleInterface
   */
  protected $fieldValidationRule;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_validation_rule_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\field_validation\FieldValidationRuleSetInterface $field_validation_rule_set
   *   The field_validation_rule_set.
   * @param string $field_validation_rule
   *   The field_validation_rule ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldValidationRuleSetInterface $field_validation_rule_set = NULL, $field_validation_rule = NULL, $field_name='') {
    $this->fieldValidationRuleSet = $field_validation_rule_set;
    try {
      $this->fieldValidationRule = $this->prepareFieldValidationRule($field_validation_rule);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid field_validation_rule id: '$field_validation_rule'.");
    }
    $request = $this->getRequest();

    if (!($this->fieldValidationRule instanceof ConfigurableFieldValidationRuleInterface)) {
      throw new NotFoundHttpException();
    }

    //$form['#attached']['library'][] = 'field_validation/admin';
    $form['uuid'] = [
      '#type' => 'hidden',
      '#value' => $this->fieldValidationRule->getUuid(),
    ];
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $this->fieldValidationRule->getPluginId(),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Validation Rule title'),
      '#default_value' => $this->fieldValidationRule->getTitle(),
      '#required' => TRUE,
    ];
	$entity_type_id = $this->fieldValidationRuleSet->getAttachedEntityType();
	$bundle = $this->fieldValidationRuleSet->getAttachedBundle();
	//$field_options = array();
    $field_options = array(
      '' => $this->t('- Select -'),
    );

	$baseFieldDefinitions = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
    foreach ($baseFieldDefinitions as $base_field_name => $base_field_definition) {
      $field_options[$base_field_name] = $base_field_definition->getLabel();
    }

	$fieldDefinitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);
	foreach ($fieldDefinitions as $fieldname => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $field_options[$fieldname] = $field_definition->getLabel();
      }
	}
    $default_field_name = $this->fieldValidationRule->getFieldName();
	if(!empty($field_name)){
	  $default_field_name = $field_name;
	}
    $user_input = $form_state->getUserInput();
    $default_field_name =  isset($user_input['field_name']) ? $user_input['field_name'] : $default_field_name;

    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field name'),
	  '#options' => $field_options,
      '#default_value' => $default_field_name,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateColumn'],
        'wrapper' => 'edit-field-name-wrapper',
		'event' => 'change',
      ],
    ];

    $default_column = $this->fieldValidationRule->getColumn();
    $default_column =  isset($user_input['column']) ? $user_input['column'] : $default_column;
    $column_options = $this->findColumn($default_field_name);
    if(!in_array($default_column, $column_options)){
      $default_column = "";
    }

    $form['column'] = [
      '#type' => 'select',
      '#title' => $this->t('Column of field'),
	  '#options' => $column_options,
      '#default_value' => $default_column,
      '#required' => TRUE,
      '#prefix' => '<div id="edit-field-name-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
    ];
    $form['data'] = $this->fieldValidationRule->buildConfigurationForm([], $form_state);
    $form['error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message'),
      '#default_value' => $this->fieldValidationRule->getErrorMessage(),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];
    $form['data']['#tree'] = TRUE;

    // Check the URL for a weight, then the fieldValidationRule, otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->fieldValidationRule->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->fieldValidationRuleSet->toUrl('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateColumn($form, FormStateInterface $form_state) {
    return $form['column'];
  }
  /**
   * Handles switching the configuration type selector.
   */
  protected function findColumn($field_name) {
    $column_options = [
      '' => $this->t('- Select -'),
    ];
	if(empty($field_name)){
	  return $column_options;
	}
	$entity_type_id = $this->fieldValidationRuleSet->getAttachedEntityType();
	$baseFieldDefinitions = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($entity_type_id);
    $schema = [];
	if(isset($baseFieldDefinitions[$field_name])){
      $field_info = $baseFieldDefinitions[$field_name];
      $schema = $field_info->getSchema();
	}else{
      $field_info = FieldStorageConfig::loadByName($entity_type_id, $field_name);
      $schema = $field_info->getSchema();
	}

	if(!empty($schema['columns'])){
	  $columns = $schema['columns'];
	  foreach($columns as $key=>$value){
	    $column_options[$key] = $key;
	  }
	}
    return $column_options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The fieldValidationRule configuration is stored in the 'data' key in the form,
    // pass that through for validation.
    $data = $form_state->getValue('data');
    if(empty($data)){
      $data = [];
    }
    $field_validation_rule_data = (new FormState())->setValues($data);
    $this->fieldValidationRule->validateConfigurationForm($form, $field_validation_rule_data);
    // Update the original form values.
    $form_state->setValue('data', $field_validation_rule_data->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    //drupal_flush_all_caches();
    // Clear all plugin caches.
    \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

    // The fieldValidationRule configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $field_validation_rule_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->fieldValidationRule->submitConfigurationForm($form, $field_validation_rule_data);

    // Update the original form values.
    $form_state->setValue('data', $field_validation_rule_data->getValues());
    $this->fieldValidationRule->setTitle($form_state->getValue('title'));
    $this->fieldValidationRule->setWeight($form_state->getValue('weight'));
    $this->fieldValidationRule->setFieldName($form_state->getValue('field_name'));
    $this->fieldValidationRule->setColumn($form_state->getValue('column'));
    $this->fieldValidationRule->setErrorMessage($form_state->getValue('error_message'));
    if (!$this->fieldValidationRule->getUuid()) {
      $this->fieldValidationRuleSet->addFieldValidationRule($this->fieldValidationRule->getConfiguration());
    }else{
      $this->fieldValidationRuleSet->deleteFieldValidationRule($this->fieldValidationRule);
      $this->fieldValidationRuleSet->addFieldValidationRule($this->fieldValidationRule->getConfiguration());
    }
    $this->fieldValidationRuleSet->save();
    $this->messenger()->addMessage($this->t('The rule was successfully applied.'));
    $form_state->setRedirectUrl($this->fieldValidationRuleSet->toUrl('edit-form'));
  }

  /**
   * Converts a field_validation_rule ID into an object.
   *
   * @param string $field_validation_rule
   *   The field_validation_rule ID.
   *
   * @return \Drupal\field_validation\FieldValidationRuleInterface
   *   The field_validation_rule object.
   */
  abstract protected function prepareFieldValidationRule($field_validation_rule);

}
