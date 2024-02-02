<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media_migration\FileEntityDealerManagerInterface;
use Drupal\media_migration\FileEntityDealerPluginInterface;
use Drupal\media_migration\Plugin\media_migration\file_entity\Audio;
use Drupal\media_migration\Plugin\media_migration\file_entity\Document;
use Drupal\media_migration\Plugin\media_migration\file_entity\Fallback;
use Drupal\media_migration\Plugin\media_migration\file_entity\Image;
use Drupal\media_migration\Plugin\media_migration\file_entity\Video;
use Drupal\media_migration\Plugin\media_migration\file_entity\Vimeo;
use Drupal\media_migration\Plugin\media_migration\file_entity\Youtube;

/**
 * Tests the file entity dealer manager and the plugin instances.
 *
 * @group media_migration
 */
class FileEntityDealerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_migration',
    'migrate',
  ];

  /**
   * Tests the file entity dealer manager.
   *
   * @param string $type
   *   The incoming file entity type ID.
   * @param string $scheme
   *   The scheme of the actual file entity.
   *
   * @dataProvider providerTestFileEntityDealer
   */
  public function testFileEntityDealerManager(string $type, string $scheme): void {
    $dealer = $this->container->get('plugin.manager.file_entity_dealer');
    $this->assertInstanceOf(FileEntityDealerManagerInterface::class, $dealer);
    assert($dealer instanceof FileEntityDealerManagerInterface);

    $plugin_instance = $dealer->createInstanceFromTypeAndScheme($type, $scheme);

    $this->assertInstanceOf(FileEntityDealerPluginInterface::class, $plugin_instance);
  }

  /**
   * Tests the file entity dealer plugin instances.
   *
   * @param string $type
   *   The incoming file entity type ID.
   * @param string $scheme
   *   The scheme of the actual file entity.
   * @param string|null $expected_plugin_class
   *   The expected class of the file entity dealer plugin instance.
   * @param string|null $expected_type_id_base
   *   The expected media type ID base returned by the file entity dealer
   *   plugin instance.
   * @param string|null $expected_type_id
   *   The expected media type ID returned by the file entity dealer plugin
   *   instance.
   * @param string|null $expected_type_label
   *   The expected media type label returned by the file entity dealer
   *   plugin instance.
   * @param string|null $expected_source_plugin_id
   *   The expected media source plugin ID from the file entity dealer plugin.
   * @param string|null $expected_source_field_name
   *   The expected media source field name from the file entity dealer plugin.
   * @param string|null $expected_source_field_label
   *   The expected media source field label from the file entity dealer plugin.
   *
   * @dataProvider providerTestFileEntityDealer
   */
  public function testFileEntityDealerPlugins(string $type, string $scheme, string $expected_plugin_class = NULL, string $expected_type_id_base = NULL, string $expected_type_id = NULL, string $expected_type_label = NULL, string $expected_source_plugin_id = NULL, string $expected_source_field_name = NULL, string $expected_source_field_label = NULL): void {
    $expectations = [
      'Media type ID base' => $expected_type_id_base,
      'Media type ID' => $expected_type_id,
      'Media type label' => $expected_type_label,
      'Media source plugin ID' => $expected_source_plugin_id,
      'Media source field name' => $expected_source_field_name,
      'Media source field label ID' => $expected_source_field_label,
    ];
    // If expectations are empty, we skip testing this case.
    if (empty(array_filter($expectations))) {
      $this->markTestSkipped();
    }

    $dealer = $this->container->get('plugin.manager.file_entity_dealer');
    assert($dealer instanceof FileEntityDealerManagerInterface);
    $plugin_instance = $dealer->createInstanceFromTypeAndScheme($type, $scheme);
    $this->assertInstanceOf($expected_plugin_class, $plugin_instance);

    $this->assertEquals(
      [
        'Media type ID base' => $expected_type_id_base,
        'Media type ID' => $expected_type_id,
        'Media type label' => $expected_type_label,
        'Media source plugin ID' => $expected_source_plugin_id,
        'Media source field name' => $expected_source_field_name,
        'Media source field label ID' => $expected_source_field_label,
      ],
      [
        'Media type ID base' => $plugin_instance->getDestinationMediaTypeIdBase(),
        'Media type ID' => $plugin_instance->getDestinationMediaTypeId(),
        'Media type label' => $plugin_instance->getDestinationMediaTypeLabel(),
        'Media source plugin ID' => $plugin_instance->getDestinationMediaSourcePluginId(),
        'Media source field name' => $plugin_instance->getDestinationMediaSourceFieldName(),
        'Media source field label ID' => $plugin_instance->getDestinationMediaTypeSourceFieldLabel(),
      ]
    );
  }

  /**
   * Data provider for ::testFileEntityDealerPlugins.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestFileEntityDealer(): array {
    return [
      'Audio type, public scheme' => [
        'Type' => 'audio',
        'Scheme' => 'public',
        'Expected plugin class' => Audio::class,
        'Expected type ID base' => 'audio',
        'Expected type ID' => 'audio',
        'Expected type label' => 'Audio',
        'Expected media source plugin ID' => 'audio_file',
        'Expected media source field name' => 'field_media_audio_file',
        'Expected media source field label of the type' => 'Audio file',
      ],
      'Audio type, custom scheme' => [
        'Type' => 'audio',
        'Scheme' => 'custom',
        'Expected plugin class' => Audio::class,
        'Expected type ID base' => 'audio',
        'Expected type ID' => 'audio_custom',
        'Expected type label' => 'Audio (custom)',
        'Expected media source plugin ID' => 'audio_file',
        'Expected media source field name' => 'field_media_audio_file_custom',
        'Expected media source field label of the type' => 'Audio file',
      ],
      'Document type, public scheme' => [
        'Type' => 'document',
        'Scheme' => 'public',
        'Expected plugin class' => Document::class,
        'Expected type ID base' => 'document',
        'Expected type ID' => 'document',
        'Expected type label' => 'Document',
        'Expected media source plugin ID' => 'file',
        'Expected media source field name' => 'field_media_document',
        'Expected media source field label of the type' => 'Document',
      ],
      'Document type, custom scheme' => [
        'Type' => 'document',
        'Scheme' => 'custom',
        'Expected plugin class' => Document::class,
        'Expected type ID base' => 'document',
        'Expected type ID' => 'document_custom',
        'Expected type label' => 'Document (custom)',
        'Expected media source plugin ID' => 'file',
        'Expected media source field name' => 'field_media_document',
        'Expected media source field label of the type' => 'Document',
      ],
      'Image type, public scheme' => [
        'Type' => 'image',
        'Scheme' => 'public',
        'Expected plugin class' => Image::class,
        'Expected type ID base' => 'image',
        'Expected type ID' => 'image',
        'Expected type label' => 'Image',
        'Expected media source plugin ID' => 'image',
        'Expected media source field name' => 'field_media_image',
        'Expected media source field label of the type' => 'Image',
      ],
      'Image type, custom scheme' => [
        'Type' => 'image',
        'Scheme' => 'custom',
        'Expected plugin class' => Image::class,
        'Expected type ID base' => 'image',
        'Expected type ID' => 'image_custom',
        'Expected type label' => 'Image (custom)',
        'Expected media source plugin ID' => 'image',
        'Expected media source field name' => 'field_media_image_custom',
        'Expected media source field label of the type' => 'Image',
      ],
      'Video type, public scheme' => [
        'Type' => 'video',
        'Scheme' => 'public',
        'Expected plugin class' => Video::class,
        'Expected type ID base' => 'video',
        'Expected type ID' => 'video',
        'Expected type label' => 'Video',
        'Expected media source plugin ID' => 'video_file',
        'Expected media source field name' => 'field_media_video_file',
        'Expected media source field label of the type' => 'Video file',
      ],
      'Video type, custom scheme' => [
        'Type' => 'video',
        'Scheme' => 'custom',
        'Expected plugin class' => Video::class,
        'Expected type ID base' => 'video',
        'Expected type ID' => 'video_custom',
        'Expected type label' => 'Video (custom)',
        'Expected media source plugin ID' => 'video_file',
        'Expected media source field name' => 'field_media_video_file_custom',
        'Expected media source field label of the type' => 'Video file',
      ],
      'Vimeo type, public scheme' => [
        'Type' => 'video',
        'Scheme' => 'vimeo',
        'Expected plugin class' => Vimeo::class,
        'Expected type ID base' => 'remote_video',
        'Expected type ID' => 'remote_video',
        'Expected type label' => 'Remote video',
        'Expected media source plugin ID' => 'oembed:video',
        'Expected media source field name' => 'field_media_oembed_video',
        'Expected media source field label of the type' => 'Video URL',
      ],
      'Youtube type, custom scheme' => [
        'Type' => 'video',
        'Scheme' => 'youtube',
        'Expected plugin class' => Youtube::class,
        'Expected type ID base' => 'remote_video',
        'Expected type ID' => 'remote_video',
        'Expected type label' => 'Remote video',
        'Expected media source plugin ID' => 'oembed:video',
        'Expected media source field name' => 'field_media_oembed_video',
        'Expected media source field label of the type' => 'Video URL',
      ],
      'Custom type, public scheme' => [
        'Type' => 'randomstring',
        'Scheme' => 'public',
        'Expected plugin class' => Fallback::class,
        'Expected type ID base' => 'randomstring',
        'Expected type ID' => 'randomstring',
        'Expected type label' => 'Randomstring',
        'Expected media source plugin ID' => 'file',
        'Expected media source field name' => 'field_media_randomstring',
        'Expected media source field label of the type' => 'Randomstring',
      ],
      'Custom type, custom scheme' => [
        'Type' => 'randomstring',
        'Scheme' => 'custom',
        'Expected plugin class' => Fallback::class,
        'Expected type ID base' => 'randomstring',
        'Expected type ID' => 'randomstring_custom',
        'Expected type label' => 'Randomstring (custom)',
        'Expected media source plugin ID' => 'file',
        'Expected media source field name' => 'field_media_randomstring_custom',
        'Expected media source field label of the type' => 'Randomstring',
      ],
    ];
  }

}
