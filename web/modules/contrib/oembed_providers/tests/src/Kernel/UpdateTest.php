<?php

namespace Drupal\Tests\oembed_providers\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_providers\Entity\ProviderBucket;

/**
 * Tests update functions.
 *
 * @group oembed_providers
 */
class UpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * Disable check of config schema so update functions can be tested.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'oembed_providers',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['oembed_providers']);
  }

  /**
   * Tests oembed_providers_update_8201().
   */
  public function testUpdate8201() {
    // Write config with removed 'allowed_providers' value.
    \Drupal::service('config.factory')->getEditable('oembed_providers.settings')->setData([
      'external_fetch' => TRUE,
      'allowed_providers' => [
        'youtube',
        'another_provider',
      ],
    ])->save();

    // Run update function.
    \Drupal::service('module_handler')->loadInclude('oembed_providers', 'install');
    oembed_providers_update_8201();

    // Verify that a 'video' provider bucket was created and that the providers
    // were property set.
    $video_bucket = ProviderBucket::load('video');
    $this->assertEquals(['youtube', 'another_provider'], $video_bucket->get('providers'));
  }

}
