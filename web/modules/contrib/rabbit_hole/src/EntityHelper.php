<?php

namespace Drupal\rabbit_hole;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides operations for bundles configuration.
 */
class EntityHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The interface for an entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * The info about entity type bundles.
   *
   * @var array
   */
  protected array $bundleInfo = [];

  /**
   * Constructs an EntityHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The interface for an entity display repository.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Returns supported entity types.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   Objects of entity types that can be configured.
   */
  public function getSupportedEntityTypes(): array {
    $entity_types = $this->entityTypeManager->getDefinitions();
    asort($entity_types);
    return array_filter($entity_types, [$this, 'entityTypeIsSupported']);
  }

  /**
   * Checks if an entity type is supported.
   *
   * @return bool
   *   TRUE if an entity type is enabled, FALSE otherwise.
   */
  public function entityTypeIsSupported(EntityTypeInterface $entity_type): bool {
    return $entity_type instanceof ContentEntityTypeInterface && $entity_type->hasLinkTemplate('canonical');
  }

  /**
   * Checks whether an entity type does not provide bundles.
   *
   * @return bool
   *   TRUE if the entity type is atomic and FALSE otherwise.
   */
  public function entityTypeHasBundles(EntityTypeInterface $entity_type): bool {
    return !empty($entity_type->getBundleEntityType()) && !empty($this->getBundleInfo($entity_type->id()));
  }

  /**
   * Gets the bundle info of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An array of bundle information.
   */
  public function getBundleInfo(string $entity_type_id): array {
    if (!isset($this->bundleInfo[$entity_type_id])) {
      $bundle_info = &$this->bundleInfo[$entity_type_id];
      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      uasort($bundle_info, function ($a, $b) {
        return SortArray::sortByKeyString($a, $b, 'label');
      });
    }
    return $this->bundleInfo[$entity_type_id];
  }

  /**
   * Gets the label for the bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle_name
   *   The entity bundle.
   *
   * @return string
   *   The bundle label.
   */
  public function getBundleLabel(string $entity_type_id, ?string $bundle_name) {
    return $this->getBundleInfo($entity_type_id)[$bundle_name]['label'] ?? $bundle_name;
  }

  /**
   * Adds a rabbit hole settings field to the given bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createRabbitHoleField(string $entity_type_id, ?string $bundle = NULL): void {
    $bundle = $bundle ?? $entity_type_id;
    $field_name = BehaviorSettingsManager::FIELD_NAME;
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if (!$field) {
      $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
      if (!$field_storage) {
        $field_storage = FieldStorageConfig::create([
          'entity_type' => $entity_type_id,
          'field_name' => $field_name,
          'type' => 'rabbit_hole',
          'locked' => TRUE,
        ]);
        $field_storage->setTranslatable(TRUE);
        $field_storage->save();
      }

      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'label' => 'Rabbit Hole settings',
      ]);
      $field->setTranslatable(TRUE);
      $field->save();

      // Assign widget settings for the default form mode.
      $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle)
        ->setComponent($field_name, [
          'type' => 'rabbit_hole_default',
          'weight' => 100,
          'settings' => [],
        ])->save();
      // Hide field on the view display.
      $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle)
        ->removeComponent($field_name)->save();
    }
  }

  /**
   * Removes a rabbit hole settings field if it is no longer needed.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   */
  public function removeRabbitHoleField(string $entity_type_id, ?string $bundle = NULL): void {
    $bundle = $bundle ?? $entity_type_id;
    if ($field = FieldConfig::loadByName($entity_type_id, $bundle, BehaviorSettingsManager::FIELD_NAME)) {
      $field->delete();
    }
  }

  /**
   * Checks whether a rabbit hole settings field exists in given bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   */
  public function hasRabbitHoleField(string $entity_type_id, ?string $bundle = NULL): bool {
    if (!empty($bundle)) {
      return (bool) FieldConfig::loadByName($entity_type_id, $bundle, BehaviorSettingsManager::FIELD_NAME);
    }
    return (bool) FieldStorageConfig::loadByName($entity_type_id, BehaviorSettingsManager::FIELD_NAME);
  }

  /**
   * Checks whether a rabbit hole values exist in the database.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   */
  public function hasFieldValues(string $entity_type_id, ?string $bundle = NULL): bool {
    if (!$this->hasRabbitHoleField($entity_type_id, $bundle)) {
      return FALSE;
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery()
      ->accessCheck(FALSE)
      ->condition(BehaviorSettingsManager::FIELD_NAME, 'bundle_default', '<>');

    if (!empty($bundle) && $bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $bundle);
    }
    return (bool) $query->count()->execute();
  }

}
