<?php

namespace Drupal\Tests\filefield_paths\Functional;

use Drupal\Tests\file\Functional\FileFieldTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class for File (Field) Paths tests.
 */
abstract class FileFieldPathsTestBase extends FileFieldTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * Node bundle ID.
   *
   * @var string|null
   */
  public $contentType = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'filefield_paths_test',
    'file_test',
    'image',
    'token',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Sets up a Drupal site for running functional and integration tests.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $content_type = $this->drupalCreateContentType();
    $this->contentType = $content_type->get('name');
  }

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $third_party_settings
   *   Third party settings.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createFileField($name, $entity_type, $bundle, $storage_settings = [], $field_settings = [], $third_party_settings = [], $widget_settings = []) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $field_storage = $entity_type_manager->getStorage('field_storage_config')
      ->create([
        'entity_type' => $entity_type,
        'field_name' => $name,
        'type' => 'file',
        'settings' => $storage_settings,
        'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
      ]);
    $field_storage->save();

    $field = [
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'third_party_settings' => $third_party_settings,
    ];
    $entity_type_manager->getStorage('field_config')
      ->create($field)
      ->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $edr */
    $edr = \Drupal::service('entity_display.repository');
    $edr->getFormDisplay($entity_type, $bundle, 'default')
      ->setComponent($name, [
        'type' => 'file_generic',
        'settings' => $widget_settings,
      ])
      ->save();
    // Assign display settings.
    $edr->getViewDisplay($entity_type, $bundle, 'default')
      ->setComponent($name, [
        'label' => 'hidden',
        'type' => 'file_default',
      ])
      ->save();

    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$name}");
    $this->submitForm([], 'Save settings');
    $this->assertSession()->pageTextContains("Saved {$name} configuration");

    // Clear field cache in order to avoid stale cache values.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $third_party_settings
   *   Third party settings.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A image field configuration entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createImageField($name, $type_name, array $storage_settings = [], array $field_settings = [], array $third_party_settings = [], array $widget_settings = []) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $field_storage = $entity_type_manager->getStorage('field_storage_config')
      ->create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => 'image',
        'settings' => $storage_settings,
        'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
      ]);
    $field_storage->save();

    $field_config = $entity_type_manager->getStorage('field_config')
      ->create([
        'field_name' => $name,
        'label' => $name,
        'entity_type' => 'node',
        'bundle' => $type_name,
        'required' => !empty($field_settings['required']),
        'settings' => $field_settings,
        'third_party_settings' => $third_party_settings,
      ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $edr */
    $edr = \Drupal::service('entity_display.repository');
    $edr->getFormDisplay('node', $type_name, 'default')
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();

    $edr->getViewDisplay('node', $type_name, 'default')
      ->setComponent($name)
      ->save();

    $this->drupalGet("admin/structure/types/manage/{$this->contentType}/fields/node.{$this->contentType}.{$name}");
    $this->submitForm([], 'Save settings');

    return $field_config;
  }

}
