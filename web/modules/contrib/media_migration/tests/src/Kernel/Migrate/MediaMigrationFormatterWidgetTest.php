<?php

namespace Drupal\Tests\media_migration\Kernel\Migrate;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForMediaSourceTrait;

/**
 * Tests widgets and formatters.
 *
 * @group media_migration
 */
class MediaMigrationFormatterWidgetTest extends MediaMigrationTestBase {

  use MediaMigrationAssertionsForMediaSourceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'editor',
    'embed',
    'entity_embed',
    'field',
    'file',
    'filter',
    'image',
    'link',
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'menu_ui',
    'migmag_process',
    'migrate',
    'migrate_drupal',
    'migrate_plus',
    'node',
    'options',
    'smart_sql_idmap',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
  ];

  /**
   * Tests media widgets' and formatters' migration without Media Library.
   *
   * @dataProvider providerTestCases
   */
  public function testWidgetsAndFormattersWithoutMediaLibrary(bool $classic_node_migration) {
    $this->createStandardMediaTypes();
    $this->setClassicNodeMigration($classic_node_migration);

    // Execute the media migrations.
    $this->executeMediaMigrations($classic_node_migration);

    $media_fields = [
      'field_image' => [
        'form' => 'image_image',
        'view' => 'entity_reference_entity_view',
      ],
      'field_media' => [
        'form' => 'file_generic',
        'view' => 'entity_reference_entity_view',
      ],
    ];
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    // Check default widget configurations. Every field widget should use.
    foreach ($media_fields as $field_name => $expected_config) {
      $entity_form_display = $entity_type_manager->getStorage('entity_form_display')->load(implode('.', [
        'node',
        'article',
        'default',
      ]));
      $entity_form_display_array = $entity_form_display->toArray();
      $content = $entity_form_display_array['content'];

      $this->assertTrue(array_key_exists($field_name, $content));
      $this->assertEquals($expected_config['form'], $content[$field_name]['type']);
    }

    // Check default formatter configurations.
    foreach ($media_fields as $field_name => $expected_config) {
      $entity_form_display = $entity_type_manager->getStorage('entity_view_display')->load(implode('.', [
        'node',
        'article',
        'default',
      ]));
      $entity_form_display_array = $entity_form_display->toArray();
      $content = $entity_form_display_array['content'];

      $this->assertTrue(array_key_exists($field_name, $content));
      $this->assertEquals($expected_config['view'], $content[$field_name]['type']);
    }

    $this->assertMediaTypeDisplayModes();
  }

  /**
   * Tests media widgets' and formatters' migration with Media Library.
   *
   * @dataProvider providerTestCases
   */
  public function testWidgetsAndFormattersWithMediaLibrary(bool $classic_node_migration) {
    $this->createStandardMediaTypes();
    $this->setClassicNodeMigration($classic_node_migration);
    $installer = $this->container->get('module_installer');
    assert($installer instanceof ModuleInstallerInterface);
    $installer->install(['media_library']);

    // Execute the media migrations.
    $this->executeMediaMigrations($classic_node_migration);

    $media_fields = [
      'field_image' => [
        'form' => 'media_library_widget',
        'view' => 'entity_reference_entity_view',
      ],
      'field_media' => [
        'form' => 'media_library_widget',
        'view' => 'entity_reference_entity_view',
      ],
    ];
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    // Check default widget configurations. Every field widget should use.
    foreach ($media_fields as $field_name => $expected_config) {
      $entity_form_display = $entity_type_manager->getStorage('entity_form_display')->load(implode('.', [
        'node',
        'article',
        'default',
      ]));
      $entity_form_display_array = $entity_form_display->toArray();
      $content = $entity_form_display_array['content'];

      $this->assertTrue(array_key_exists($field_name, $content));
      $this->assertEquals($expected_config['form'], $content[$field_name]['type']);
    }

    // Check default formatter configurations.
    foreach ($media_fields as $field_name => $expected_config) {
      $entity_form_display = $entity_type_manager->getStorage('entity_view_display')->load(implode('.', [
        'node',
        'article',
        'default',
      ]));
      $entity_form_display_array = $entity_form_display->toArray();
      $content = $entity_form_display_array['content'];

      $this->assertTrue(array_key_exists($field_name, $content));
      $this->assertEquals($expected_config['view'], $content[$field_name]['type']);
    }

    $this->assertMediaTypeDisplayModes();
  }

  /**
   * Tests display configuration of the migrated media types.
   */
  public function assertMediaTypeDisplayModes() {
    $this->assertMediaAudioDisplayModes();
    $this->assertMediaDocumentDisplayModes();
    $this->assertMediaImageDisplayModes(TRUE);
    $this->assertMediaRemoteVideoDisplayModes();
    $this->assertMediaVideoDisplayModes();
  }

  /**
   * Data provider for ::testMediaTokenToMediaEmbedTransform().
   *
   * @return array
   *   The test cases.
   */
  public function providerTestCases() {
    $test_cases = [
      'Classic node migration' => [
        'Classic node migration' => TRUE,
      ],
      'Complete node migration' => [
        'Classic node migration' => FALSE,
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      $test_cases = array_filter($test_cases, function ($test_case) {
        return $test_case['Classic node migration'];
      });
    }

    return $test_cases;
  }

}
