<?php

namespace Drupal\video_embed_field_migrate;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\MediaType;

/**
 * VideoEmbedMigrate service.
 */
class VideoEmbedFieldMigrate {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a VideoEmbedMigrate object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepository $entity_display_repository) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeManager->useCaches(FALSE);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * @return \Drupal\media\Entity\MediaType|bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRemoteVideoMediaType() {
    $types = $this->entityTypeManager->getStorage('media_type')->loadByProperties(['source' => 'oembed:video']);
    return !empty($types) ? reset($types) : FALSE;
  }

  /**
   * @param string $fieldName
   * @param array $entityTypes
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isFieldNameAvailable(string $fieldName, array $entityTypes) : bool {
    $fieldConfigQuery = $this->entityTypeManager->getStorage('field_config')->getQuery();
    $fieldConfigQuery->condition('field_name', $fieldName);
    $fieldConfigQuery->condition('entity_type', $entityTypes, 'IN');
    return empty($fieldConfigQuery->execute());
  }

  /**
   * Find the video_embed_field field to migrate.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findFieldsToMigrate() : array {
    $fields = [];
    $fieldConfigStorage = $this->entityTypeManager->getStorage('field_config');
    $fieldConfigQuery = $fieldConfigStorage->getQuery();
    $fieldConfigQuery->condition('entity_type', 'media', '<>');
    $fieldConfigQuery->condition('field_type', 'video_embed_field');
    $result = $fieldConfigQuery->execute();

    foreach($result as $id) {
      /** @var \Drupal\field\Entity\FieldConfig $fieldConfig */
      $fieldConfig = $fieldConfigStorage->load($id);
      $fields[] = [
        'field_name' => $fieldConfig->getName(),
        'entity_type' => $fieldConfig->getTargetEntityTypeId(),
        'bundle' => $fieldConfig->getTargetBundle(),
        'cardinality' => $fieldConfig->getFieldStorageDefinition()->get('cardinality'),
      ];
    }
    return $fields;
  }

  /**
   * Get the field values for a video_embed_field
   *
   * @param string $fieldName
   * @param string $entityType
   * @param string $bundle
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFieldValues(string $fieldName, string $entityType, string $bundle) : array {
    $values = [];
    $storage = $this->entityTypeManager->getStorage($entityType);
    $query = $storage->getQuery();
    $query->condition('type', $bundle);
    $query->exists($fieldName);
    $ids = $query->execute();
    if (!empty($ids)) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      foreach($storage->loadMultiple(array_values($ids)) as $paragraph) {
        $values[$paragraph->id()] = $paragraph->get($fieldName)->value;
      }
    }
    return $values;
  }

  public function preFlight() {
    $mediaSettings = $this->configFactory->getEditable('media.settings');
    if (empty($mediaSettings->get('oembed_providers_url'))) {
      $mediaSettings->set('oembed_providers_url', 'https://oembed.com/providers.json');
      $mediaSettings->save();
    }
  }

  /**
   * @return \Drupal\media\Entity\MediaType
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createRemoteVideoMediaType() : MediaType {
    $media_type = MediaType::create([
      'uuid' => 'c87fd6c4-d5dc-4ccb-b645-d24e9dc3bdb9',
      'label' => 'Remote video',
      'id' => 'remote_video',
      'description' => 'A remotely hosted video from YouTube or Vimeo.',
      'source' => 'oembed:video',
      'field_map' => [],
      'queue_thumbnail_downloads' => false,
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->set('uuid', 'dc98c08c-f48d-417b-b0e2-09c01e76d3a5');
    $source_field->getFieldStorageDefinition()->set('uuid', '9f8b60d5-374f-4249-b6b6-d77481298e1a')->save();
    $source_field->save();
    $media_type
      ->set('source_configuration', [
        'source_field' => $source_field->getName(),
        'thumbnails_directory' => 'public://oembed_thumbnails',
        'providers' => [
          0 => 'YouTube',
          1 => 'Vimeo',
        ],
      ])
      ->save();

    $standardPath = drupal_get_path('profile', 'standard');
    $configs = [
      'core.entity_form_display.media.remote_video.default' => '/config/optional/core.entity_form_display.media.remote_video.default.yml',
      'core.entity_view_display.media.remote_video.default' => '/config/optional/core.entity_view_display.media.remote_video.default.yml'
    ];
    $uuids = [
      '7e5df004-373f-409c-b0bc-fe85c923be9e',
      '6cdd9c6a-4a00-4071-bc02-a718a4472dae',
    ];
    foreach ($configs as $name => $path) {
      $fileContents = file_get_contents($standardPath . $path);
      $editable = \Drupal::configFactory()->getEditable($name);
      if ($editable->isNew()) {
        $config = \Symfony\Component\Yaml\Yaml::parse($fileContents);
        $config['uuid'] = array_pop($uuids);
        $editable->setData($config);
        $editable->save();
      }
    }
    return $media_type;
  }

  /**
   * @param string $fieldName
   * @param array $bundles
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createReferenceFields(string $fieldName, array $bundles) {
    $uuids = [
      '7e5df004-373f-409c-b0bc-fe85c923be9e',
      '6cdd9c6a-4a00-4071-bc02-a718a4472dae',
      '71739ae3-0f86-4c62-ba49-e9eff7f1c7be',
      '341cf22d-a3a1-428b-970e-12945ab03b5a',
      '4eb3178a-72c6-459f-97bd-c2d335efe1c6',
      '6f71603d-c3e8-48e2-9bfe-5f894543cff9',
      'd96e892d-968d-42c5-b2b1-73f987e6b3bc',
      'ff600a9e-7ab8-40d5-8f00-257cf68cc56c',
      '3b403219-9335-457d-b960-439895e9372c',
      '757b8301-845f-439e-af9f-4e31321d44eb',
      '12137d51-2cee-4db1-8aec-cc13f2139799',
      '2c5ec3ff-8a5a-497b-8682-cfff2e45c127',
      '2f6200ba-2331-495a-93c7-7cb93a5f3f91',
      'b95b1ecf-e33b-42fe-bbdc-890ff672e426',
      '007bd47c-2bc3-48a0-872b-3b47b8887c28',
      'fabe9ea8-92db-41ef-a319-8f37841d67c8',
      '25080482-05ae-4653-8e2a-18e4b0f064b2',
      'fe337130-6f82-49b8-a7c8-eef1fddc99c3',
    ];
    $formDisplayOptions = ['type' => 'entity_reference_autocomplete'];
    $viewDisplayOptions = ['type' => 'entity_reference_entity_view', 'label' => 'hidden'];

    $storages = [];
    foreach ($bundles as $bundle) {
      if (!isset($storages[$bundle['entity_type']])) {
        $storage = $this->entityTypeManager
          ->getStorage('field_storage_config')
          ->create([
            'uuid' => array_pop($uuids),
            'entity_type' => $bundle['entity_type'],
            'field_name' => $fieldName,
            'type' => 'entity_reference',
            'settings' => [
              'target_type' => 'media',
            ],
            'cardinality' => $bundle['cardinality'],
          ]);
        $storage->save();
        $storages[$bundle['entity_type']] = $storage;
      }
      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->create([
          'uuid' => array_pop($uuids),
          'field_name' => $fieldName,
          'entity_type' => $bundle['bundle'],
          'bundle' => $bundle['bundle'],
          'field_storage' => $storages[$bundle['entity_type']],
          'label' => 'Video',
          'required' => TRUE,
          'settings' => [
            'handler' => 'default',
            'handler_settings' => [
              'target_bundles' => ['remote_video' => 'remote_video']
            ]
          ],
        ]);
      $field->save();
      $this->entityDisplayRepository->getFormDisplay($bundle['entity_type'], $bundle['bundle'], 'default')
        ->setComponent($fieldName, $formDisplayOptions)
        ->save();
      $this->entityDisplayRepository->getViewDisplay($bundle['entity_type'], $bundle['bundle'])
        ->setComponent($fieldName, $viewDisplayOptions)
        ->save();
      $this->entityTypeManager->useCaches(FALSE);
    }
  }

  /**
   * @param \Drupal\media\Entity\MediaType $mediaType
   * @param string $refFieldName
   * @param string $entityType
   * @param int $id
   * @param string $legacyField
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function migrateField(MediaType $mediaType, string $refFieldName, string $entityType, int $id, string $legacyField) {
    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $storage = $this->entityTypeManager->getStorage($entityType);
    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $fieldValues = [];

    /** @var \Drupal\Core\Entity\ContentEntityBase $targetEntity */
    $targetEntity = $storage->load($id);
    /** @var \Drupal\Core\Field\FieldItemList $videoUrlFieldItems */
    $videoUrlFieldItems = $targetEntity->get($legacyField);
    foreach ($videoUrlFieldItems->getIterator() as $videoUrlFieldItem) {
      $videoUrl = $videoUrlFieldItem->value;
      $result = $mediaStorage->getQuery()
        ->condition('bundle', $mediaType->id())
        ->condition($sourceField->getName(), $videoUrl)
        ->execute();
      if (empty($result)) {
        $videoMedia = $mediaStorage->create(['bundle' => $mediaType->id(), $sourceField->getName() => ['value' => $videoUrl]]);
        $videoMedia->save();
      }
      else {
        $videoMedia = $mediaStorage->load(reset($result));
      }
      $fieldValues[] = ['target_id' => $videoMedia->id()];
    }
    $targetEntity->set($refFieldName, $fieldValues);
    $targetEntity->save();
  }

}
