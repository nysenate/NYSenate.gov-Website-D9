<?php

namespace Drupal\webform_views_extras\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebformSubmissionRelationshipsForm.
 */
class WebformSubmissionRelationshipsForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field storage definition listener service.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * WebformSubmissionRelationshipsForm constructor.
   *
   *  The entity field manager service.
   * @param EntityFieldManagerInterface $entity_field_manager
   *  The field storage  definition listener service.
   * @param FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, FieldStorageDefinitionListenerInterface $field_storage_definition_listener) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('field_storage_definition.listener')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webform_submission_relationships = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webform_submission_relationships->label(),
      '#description' => $this->t("Label for the Webform submission relationships."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webform_submission_relationships->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webform_views_extras\Entity\WebformSubmissionRelationships::load',
      ],
      '#disabled' => !$webform_submission_relationships->isNew(),
    ];

    $form['content_entity_type_id'] = [
      '#type' => 'select',
      '#options' => webform_views_extras_content_entities(TRUE),
      '#title' => $this->t('Select the content entity type.'),
      '#description' => $this->t("Content entity targeted for creating Webform submission relationship."),
      '#default_value' => $webform_submission_relationships->getContentEntityTypeId(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform_submission_relationships = $this->entity;
    $webform_submission_relationships->set('content_entity_type_id', $form_state->getValue('content_entity_type_id'));

    $status = $webform_submission_relationships->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Webform submission relationships.', [
          '%label' => $webform_submission_relationships->label(),
        ]));
        $this->messenger()->addMessage($this->t('Please do not forget to clear all caches to add the new relationship in the webformSubmission view.'));
        break;
      default:
        $this->messenger()->addMessage($this->t('Saved the %label Webform submission relationships.', [
          '%label' => $webform_submission_relationships->label(),
        ]));
    }
    $form_state->setRedirectUrl($webform_submission_relationships->toUrl('collection'));
  }

}


