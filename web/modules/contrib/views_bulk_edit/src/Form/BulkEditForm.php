<?php

namespace Drupal\views_bulk_edit\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The bulk edit form.
 */
class BulkEditForm extends ConfirmFormBase {

  use BulkEditFormTrait;

  /**
   * Private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Count of entities to modify.
   *
   * @var int
   */
  protected $count = 0;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * BulkEditForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp store service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle info object.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository, PrivateTempStoreFactory $temp_store_factory, TimeInterface $time, AccountInterface $currentUser, EntityTypeBundleInfoInterface $bundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->tempStore = $temp_store_factory->get('entity_edit_multiple');
    $this->time = $time;
    $this->currentUser = $currentUser;
    $this->bundleInfo = $bundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bundle_data = [];
    foreach ($this->getBulkEditEntityData() as $entity_type_id => $bundle_entities) {
      $bundle_info = $this->bundleInfo->getBundleInfo($entity_type_id);
      foreach ($bundle_entities as $bundle => $entities) {
        $this->count += count($entities);
        $bundle_data[$entity_type_id][$bundle] = $bundle_info[$bundle]['label'];
      }
    }

    $form = $this->buildBundleForms($form, $form_state, $bundle_data);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->submitConfigurationForm($form, $form_state);

    foreach ($this->getBulkEditEntityData() as $entity_type_id => $bundle_entities) {
      foreach ($bundle_entities as $bundle => $entities) {

        $entities = $this->entityTypeManager->getStorage($entity_type_id)
          ->loadMultiple(array_keys($entities));
        foreach ($entities as $entity) {
          $this->execute($entity);
        }
      }
    }

    $this->clearBulkEditEntityData();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural($this->count, 'Are you sure you want to edit this entity?', 'Are you sure you want to edit these entities?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * Gets the saved entity data.
   *
   * @return array
   *   An array of saved entity data.
   */
  protected function getBulkEditEntityData() {
    return $this->tempStore->get($this->currentUser->id()) ?: [];
  }

  /**
   * Clear the saved entities once we've finished with them.
   */
  protected function clearBulkEditEntityData() {
    $this->tempStore->delete($this->currentUser->id());
  }

}
