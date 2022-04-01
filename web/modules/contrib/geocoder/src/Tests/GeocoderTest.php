<?php

namespace Drupal\geocoder\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Geocoder module.
 *
 * @group Geocoder
 */
class GeocoderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['geocoder'];

  /**
   * {@inheritdoc}
   */
  private $user;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {

    parent::setUp();
    $this->user = $this->DrupalCreateUser([
      'administer site configuration',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testMobileJsRedirectPageExists() {

    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function testConfigForm() {

    // Test form structure.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
    $config = $this->config('geocoder.settings');
    $this->assertFieldByName(
      'cache',
      $config->get('cache')
    );

    $this->drupalPostForm(NULL, [
      'cache' => FALSE,
    ], 'Save configuration');

    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
    $this->assertFieldByName(
      'cache',
      FALSE
    );
  }

}
