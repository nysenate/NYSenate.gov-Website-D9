<?php

namespace Drupal\Tests\geocoder\Kernel;

use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests basic Geocoder functionality.
 *
 * @group geocoder
 */
class GeocoderKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['geocoder'];

  /**
   * Our test provider.
   *
   * @var \Drupal\geocoder\GeocoderProviderInterface
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->provider = GeocoderProvider::create([
      'id' => 'random',
      'plugin' => 'random',
    ]);
    $this->provider->save();
  }

  /**
   * Tests the random provider geocoding.
   */
  public function testRandomGeocode() {
    /** @var \Drupal\geocoder\GeocoderInterface $geocoder */
    $geocoder = \Drupal::service('geocoder');
    $this->assertNotEmpty($geocoder->geocode('123 Foo Street', [
      $this->provider,
    ]));
  }

  /**
   * Tests the random provider geocoding with passing the provider as a string.
   */
  public function testRandomGeocodeWithString() {
    /** @var \Drupal\geocoder\GeocoderInterface $geocoder */
    $geocoder = \Drupal::service('geocoder');
    $this->assertNotEmpty($geocoder->geocode('123 Foo Street', [
      'random',
    ]));
  }

}
