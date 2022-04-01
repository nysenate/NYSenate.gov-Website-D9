<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tracks usage of entities related in Layout Builder layouts.
 *
 * @EntityUsageTrack(
 *   id = "layout_builder",
 *   label = @Translation("Layout builder"),
 *   description = @Translation("Tracks relationships in layout builder layouts."),
 *   field_types = {"layout_section"},
 * )
 */
class LayoutBuilder extends EntityUsageTrackBase {

  /**
   * Block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new LayoutBuilder plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_usage\EntityUsage $usage_service
   *   The usage tracking service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The EntityRepositoryInterface service.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   Block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityUsage $usage_service, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, EntityRepositoryInterface $entity_repository, BlockManagerInterface $blockManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $usage_service, $entity_type_manager, $entity_field_manager, $config_factory, $entity_repository);
    $this->blockManager = $blockManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_usage.usage'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    assert($item instanceof LayoutSectionItem);

    // We support both Content Blocks and Entity Browser Blocks.
    $blockContentRevisionIds = [];
    $ebbContentIds = [];
    $contentDependencyIds = [];

    /** @var \Drupal\layout_builder\Plugin\DataType\SectionData $value */
    foreach ($item as $value) {
      /** @var \Drupal\layout_builder\Section $section */
      $section = $value->getValue();
      foreach ($section->getComponents() as $component) {
        $configuration = $component->toArray()['configuration'];
        try {
          $def = $this->blockManager->getDefinition($component->getPluginId());
        }
        catch (PluginNotFoundException $e) {
          // Block has since been removed, continue.
          continue;
        }
        if ($def['id'] === 'inline_block') {
          $blockContentRevisionIds[] = $configuration['block_revision_id'];
        }
        elseif ($def['id'] === 'entity_browser_block' && !empty($configuration['entity_ids'])) {
          $ebbContentIds = array_unique(array_merge($ebbContentIds, (array) $configuration['entity_ids']));
        }

        // Check the block plugin's content dependencies.
        /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
        $plugin = $component->getPlugin();
        $dependencies = $plugin->calculateDependencies();
        if (!empty($dependencies['content'])) {
          $contentDependencyIds = array_merge($contentDependencyIds, $dependencies['content']);
        }
      }
    }

    $target_entities = [];
    if (count($blockContentRevisionIds) > 0) {
      $target_entities = $this->prepareBlockContentIds($blockContentRevisionIds);
    }
    if (count($ebbContentIds) > 0) {
      $target_entities = array_merge($target_entities, $this->prepareEntityBrowserBlockIds($ebbContentIds));
    }
    if (count($contentDependencyIds) > 0) {
      $target_entities = array_merge($target_entities, $this->prepareContentDependencyIds($contentDependencyIds));
    }
    return $target_entities;

  }

  /**
   * Prepare block content target entity values to be in the correct format.
   *
   * @param array $blockContentRevisionIds
   *   An array of block (content) revision IDs.
   *
   * @return array
   *   An array of the corresponding block IDs from the revision IDs passed in,
   *   each prefixed with the string "block_content|".
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function prepareBlockContentIds(array $blockContentRevisionIds) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $blockContentStorage */
    $blockContentStorage = $this->entityTypeManager->getStorage('block_content');

    /** @var \Drupal\block_content\BlockContentInterface[] $blockContent */
    $ids = $blockContentStorage->getQuery()
      ->condition($blockContentStorage->getEntityType()->getKey('revision'), $blockContentRevisionIds, 'IN')
      ->execute();

    return array_map(function (string $id): string {
      return 'block_content|' . $id;
    }, $ids);
  }

  /**
   * Prepare Entity Browser Block IDs to be in the correct format.
   *
   * @param array $ebbContentIds
   *   An array of entity ID values as returned from the EBB configuration.
   *   (Each value is expected to be in the format "node:123", "media:42", etc).
   *
   * @return array
   *   The same array passed in, with the following modifications:
   *   - Non-loadable entities will be filtered out.
   *   - The ":" character will be replaced by the "|" character.
   */
  private function prepareEntityBrowserBlockIds(array $ebbContentIds) {
    // Only return loadable entities.
    $ids = array_filter($ebbContentIds, function ($item) {
      // Entity Browser Block stores each entity in "entity_ids" in the format:
      // "{$entity_type_id}:{$entity_id}".
      list($entity_type_id, $entity_id) = explode(":", $item);
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if (!$storage) {
        return FALSE;
      }
      $entity = $storage->load($entity_id);
      if (!$entity) {
        return FALSE;
      }
      return TRUE;
    });

    if (empty($ids)) {
      return [];
    }

    // Return items in the expected format, separating type and id with a "|".
    return array_map(function (string $item): string {
      return str_replace(":", "|", $item);
    }, $ids);
  }

  /**
   * Prepare plugin content dependency IDs to be in the correct format.
   *
   * @param array $ids
   *   An array of entity ID values as returned from the plugin dependency
   *   configuration. (Each value is expected to be in the format
   *   "media:image:4dd39aa2-068f-11ec-9a03-0242ac130003", etc).
   *
   * @return array
   *   The same array passed in, with the following modifications:
   *   - Non-loadable entities will be filtered out.
   *   - The bundle ID in the middle will be removed.
   *   - The UUID will be converted to a regular ID.
   *   - The ":" character will be replaced by the "|" character.
   */
  private function prepareContentDependencyIds(array $ids) {
    // Only return loadable entities.
    $ids = array_map(function ($item) {
      // Content dependencies are stored in the format:
      // "{$entity_type_id}:{$bundle_id}:{$entity_uuid}".
      list($entity_type_id, , $entity_uuid) = explode(':', $item);
      if ($entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $entity_uuid)) {
        return "{$entity_type_id}|{$entity->id()}";
      }
      return FALSE;
    }, $ids);

    return array_filter($ids);
  }

}
