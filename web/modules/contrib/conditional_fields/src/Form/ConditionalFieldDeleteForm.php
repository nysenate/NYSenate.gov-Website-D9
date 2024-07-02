<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * A form to delete a conditional field.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldDeleteForm extends ConfirmFormBase {

  /**
   * The entity type this conditional field is attached to.
   *
   * @var string
   */
  private $entityType;

  /**
   * The bundle type this conditional field is attached to.
   *
   * @var string
   */
  private $bundle;

  /**
   * The name of the conditional field to delete.
   *
   * @var string
   */
  private $fieldName;

  /**
   * The UUID of the conditional field to delete.
   *
   * @var string
   */
  private $uuid;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %field_name condition?', [
      '%field_name' => $this->fieldName,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('conditional_fields.conditions_list', [
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->entityType) || empty($this->bundle) || empty($this->fieldName) || empty($this->uuid)) {
      return;
    }
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($this->entityType . '.' . $this->bundle . '.default');
    if (!$entity) {
      return;
    }
    $field = $entity->getComponent($this->fieldName);
    unset($field['third_party_settings']['conditional_fields'][$this->uuid]);
    $entity->setComponent($this->fieldName, $field);
    $entity->save();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL, $field_name = NULL, $uuid = NULL) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
    $this->fieldName = $field_name;
    $this->uuid = $uuid;

    return parent::buildForm($form, $form_state);
  }

}
