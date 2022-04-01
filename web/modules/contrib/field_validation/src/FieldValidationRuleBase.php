<?php

namespace Drupal\field_validation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for FieldValidationRule.
 *
 * @see \Drupal\field_validation\Annotation\FieldValidationRule
 * @see \Drupal\field_validation\FieldValidationRuleInterface
 * @see \Drupal\field_validation\ConfigurableFieldValidationRuleInterface
 * @see \Drupal\field_validation\ConfigurableFieldValidationRuleBase
 * @see \Drupal\field_validation\FieldValidationRuleManager
 * @see plugin_api
 */
abstract class FieldValidationRuleBase extends PluginBase implements FieldValidationRuleInterface, ContainerFactoryPluginInterface {

  /**
   * The FieldValidationRule ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the FieldValidationRule.
   *
   * @var int|string
   */
  protected $weight = '';
  
  /**
   * The title of the FieldValidationRule.
   *
   * @var string
   */
  protected $title = '';
  
  /**
   * The field name of the FieldValidationRule.
   *
   * @var string
   */
  protected $field_name = '';

  /**
   * The column of the FieldValidationRule.
   *
   * @var string
   */
  protected $column = '';
  /**
   * The error message of the FieldValidationRule.
   *
   * @var string
   */
  protected $error_message = '';
  
  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('field_validation')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    // Most tabs will not change the extension. This base
    // implementation represents this behavior. Override this method if your
    // tab does change the extension.
    return $extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => '',
      '#tab' => [
        'id' => $this->pluginDefinition['id'],
        'label' => $this->label(),
        'description' => $this->pluginDefinition['description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'title' => $this->getTitle(),
      'weight' => $this->getWeight(),
      'field_name' => $this->getFieldName(),
      'column' => $this->getColumn(),
      'error_message' => $this->getErrorMessage(),
      'data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
      'uuid' => '',
      'title' => '',
      'weight' => '',
      'field_name' => '',
      'column' => '',
      'error_message' => '',
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->uuid = $configuration['uuid'];
    $this->title = $configuration['title'];
    $this->weight = $configuration['weight'];
    $this->field_name = $configuration['field_name'];
    $this->column = $configuration['column'];
    $this->error_message = $configuration['error_message'];
    return $this;
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
  public function calculateDependencies() {
    return [];
  }
  /**
   * Returns the field name of the field_validation_rule.
   *
   * @return string
   *   The field name of the field_validation_rule.
   */
  public function getFieldName(){
    return $this->field_name;
  }
  /**
   * Sets the field name for this field_validation_rule.
   *
   * @param int $field_name
   *   The field name for this field_validation_rule.
   *
   * @return $this
   */
  public function setFieldName($field_name) {
    $this->field_name = $field_name;
    return $this;
  }
  /**
   * Returns the column of the field_validation_rule.
   *
   * @return string
   *   The column of the field_validation_rule.
   */
  public function getColumn(){
    return $this->column;
  }
  /**
   * Sets the column for this field_validation_rule.
   *
   * @param int $column
   *   The column for this field_validation_rule.
   *
   * @return $this
   */
  public function setColumn($column){
    $this->column = $column;
    return $this;
  }
  
  /**
   * Returns the error message of the field_validation_rule.
   *
   * @return string
   *   The error message of the field_validation_rule.
   */
  public function getErrorMessage(){
    return $this->error_message;
  }
  /**
   * Sets the error message for this field_validation_rule.
   *
   * @param int $error_message
   *   The error message for this field_validation_rule.
   *
   * @return $this
   */
  public function setErrorMessage($error_message){
    $this->error_message = $error_message;
    return $this;
  }  
  
  /**
   * {@inheritdoc}
   */  

   public function validate($params) {
    return true;
  }

}
