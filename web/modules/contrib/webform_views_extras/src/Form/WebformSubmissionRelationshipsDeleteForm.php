<?php

namespace Drupal\webform_views_extras\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Webform submission relationships entities.
 */
class WebformSubmissionRelationshipsDeleteForm extends EntityConfirmFormBase {

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
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * WebformSubmissionRelationshipsForm constructor.
   *
   *  The entity field manager service.
   * @param EntityFieldManagerInterface $entity_field_manager
   *  The field storage  definition listener service.
   * @param FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, FieldStorageDefinitionListenerInterface $field_storage_definition_listener, CachedDiscoveryClearerInterface $plugin_cache_clearer) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;
    $this->pluginCacheClearer = $plugin_cache_clearer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('field_storage_definition.listener'),
      $container->get('plugin.cache_clearer')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.webform_submission_relationships.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->messenger()->addMessage(
      $this->t('content @type: deleted @label.', [
        '@type' => $this->entity->bundle(),
        '@label' => $this->entity->label(),
      ])
    );
    $form_state->setRedirectUrl($this->getCancelUrl());

  }

}
