<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Helper class for working with entities.
 *
 * @package Drupal\simple_sitemap
 */
class EntityHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityHelper constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->configFactory = $configFactory;
  }

  /**
   * @param string $entity_type_id
   * @return array
   */
  public function getBundleInfo($entity_type_id) {
    return $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
  }

  /**
   * @param string $entity_type_id
   * @param string $bundle_name
   * @return mixed
   */
  public function getBundleLabel($entity_type_id, $bundle_name) {
    $entity_info = $this->getBundleInfo($entity_type_id);

    return isset($entity_info[$bundle_name]['label'])
      ? $entity_info[$bundle_name]['label']
      : $bundle_name; // Menu fix.
  }

  /**
   * Gets an entity's bundle name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the bundle name for.
   *
   * @return string
   *   The bundle of the entity.
   */
  public function getEntityInstanceBundleName(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'menu_link_content'
      // Menu fix.
      ? $entity->getMenuName() : $entity->bundle();
  }

  /**
   * Gets the entity type id for a bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get an entity type id for a bundle.
   *
   * @return null|string
   *   The entity type for a bundle or NULL on failure.
   */
  public function getBundleEntityTypeId(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'menu'
      // Menu fix.
      ? 'menu_link_content' : $entity->getEntityType()->getBundleOf();
  }

  /**
   * Returns objects of entity types that can be indexed.
   *
   * @return array
   *   Objects of entity types that can be indexed by the sitemap.
   */
  public function getSupportedEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), [$this, 'supports']);
  }

  /**
   * Determines if an entity type is supported or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @return bool
   *   TRUE if entity type supported by Simple Sitemap, FALSE if not.
   */
  public function supports(EntityTypeInterface $entity_type) {
    if (!$entity_type instanceof ContentEntityTypeInterface
      || !method_exists($entity_type, 'getBundleEntityType')
      || !$entity_type->hasLinkTemplate('canonical')) {
      return FALSE;
    }

    return TRUE;
   }

  /**
   * Checks whether an entity type does not provide bundles.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool
   *   TRUE if the entity type is atomic and FALSE otherwise.
   */
  public function entityTypeIsAtomic($entity_type_id) {

    // Menu fix.
    if ($entity_type_id === 'menu_link_content') {
      return FALSE;
    }

    $entity_types = $this->entityTypeManager->getDefinitions();

    if (!isset($entity_types[$entity_type_id])) {
      // todo: Throw exception.
    }

    return empty($entity_types[$entity_type_id]->getBundleEntityType()) ? TRUE : FALSE;
  }

  /**
   * Gets the entity from URL object.
   *
   * @param \Drupal\Core\Url $url_object
   *   The URL object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityFromUrlObject(Url $url_object) {
    if ($url_object->isRouted()) {

      // Fix for the homepage, see
      // https://www.drupal.org/project/simple_sitemap/issues/3194130.
      if ($url_object->getRouteName() === '<front>' &&
        !empty($uri = $this->configFactory->get('system.site')->get('page.front'))) {
        $url_object = Url::fromUri('internal:' . $uri);
      }

      if (!empty($route_parameters = $url_object->getRouteParameters())
        && $this->entityTypeManager->getDefinition($entity_type_id = key($route_parameters), FALSE)) {
          return $this->entityTypeManager->getStorage($entity_type_id)->load($route_parameters[$entity_type_id]);
      }
    }

    return NULL;
  }

  /**
   * Gets the entity IDs by entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle_name
   *   The bundle name.
   *
   * @return array
   *   An array of entity IDs
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityInstanceIds($entity_type_id, $bundle_name = NULL) {
    $sitemap_entity_types = $this->getSupportedEntityTypes();
    if (!isset($sitemap_entity_types[$entity_type_id])) {
      return [];
    }

    $entity_query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    if (!$this->entityTypeIsAtomic($entity_type_id) && NULL !== $bundle_name) {
      $keys = $sitemap_entity_types[$entity_type_id]->getKeys();

      // Menu fix.
      $keys['bundle'] = $entity_type_id === 'menu_link_content' ? 'menu_name' : $keys['bundle'];

      $entity_query->condition($keys['bundle'], $bundle_name);
    }

    return $entity_query->accessCheck(TRUE)->execute();
  }

}
