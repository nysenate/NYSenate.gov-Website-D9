<?php

namespace Drupal\Tests\oembed_providers\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_providers\Entity\ProviderBucket;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests media source integration.
 *
 * @group oembed_providers
 */
class MediaSourceTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'image',
    'user',
    'file',
    'field',
    'system',
    'oembed_providers',
  ];

  /**
   * A test provider bucket.
   *
   * @var \Drupal\oembed_providers\Entity\ProviderBucket
   */
  protected $providerBucket;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'field',
      'file',
      'system',
      'image',
      'media',
      'oembed_providers',
    ]);

    $this->providerBucket = ProviderBucket::create([
      'id' => 'test',
      'label' => 'Test Provider Bucket',
      'description' => 'This is a test provider bucket',
      'providers' => [
        'YouTube',
      ],
    ]);
    $this->providerBucket->save();
  }

  /**
   * Tests generation of media sources from provider buckets.
   */
  public function testMediaSourceGeneration() {
    /** @var \Drupal\media\MediaSourceManager */
    $media_source_manager = \Drupal::service('plugin.manager.media.source');
    $definitions = $media_source_manager->getDefinitions();

    // Verify 'oembed:test' media source was generated as expected.
    $this->assertArrayHasKey('oembed:test', $definitions);
    $expected = [
      'id' => 'test',
      'label' => 'Test Provider Bucket',
      'description' => 'This is a test provider bucket',
      'allowed_field_types' => [
        'string',
      ],
      'default_thumbnail_filename' => 'no-thumbnail.png',
      'providers' => [
        'YouTube',
      ],
      'class' => 'Drupal\oembed_providers\Plugin\media\Source\OEmbed',
      'default_name_metadata_attribute' => 'default_name',
      'thumbnail_uri_metadata_attribute' => 'thumbnail_uri',
      'forms' => [
        'media_library_add' => 'Drupal\media_library\Form\OEmbedForm',
      ],
      'provider' => 'oembed_providers',
    ];
    $actual = $definitions['oembed:test'];
    // Reset array index as it differs and doesn't matter.
    $actual['providers'] = array_values($actual['providers']);

    $this->assertSame($expected, $actual);
  }

  /**
   * Tests dependencies for oEmbed media sources.
   */
  public function testMediaSourceDependencies() {
    // Verify dependencies are correctly added to media sources generated
    // from Provider Buckets.
    $test_media_type = $this->createMediaType('oembed:test');
    $dependencies = $test_media_type->getDependencies();

    $this->assertArrayHasKey('config', $dependencies);
    $expected = [
      'oembed_providers.bucket.test',
    ];
    $this->assertSame($expected, $dependencies['config']);

    $this->assertArrayHasKey('module', $dependencies);
    $expected = [
      'oembed_providers',
    ];
    $this->assertSame($expected, $dependencies['module']);

    // Verify dependencies are not added to media sources, which are not
    // provided by the oembed_providers module.
    $core_video = $this->createMediaType('oembed:video');
    $this->assertEmpty($core_video->getDependencies());

    $core_image = $this->createMediaType('image');
    $this->assertEmpty($core_image->getDependencies());
  }

}
