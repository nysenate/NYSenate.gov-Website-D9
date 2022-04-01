<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test that it is possible to override the destination parameter.
 *
 * @group r4032login
 */
class DestinationParameterOverrideTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['r4032login'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = $this->config('r4032login.settings');
    $config->set('destination_parameter_override', 'customDestination');
    $config->save();
  }

  /**
   * Test destination parameter override.
   *
   * @param string $path
   *   Request path.
   * @param string $destination
   *   Resulting URL.
   *
   * @dataProvider destinationParameterOverrideDataProvider
   */
  public function testDestinationParameterOverride($path, $destination) {
    $this->drupalGet($path);

    $currentUrl = str_replace($this->baseUrl . '/', '', $this->getUrl());
    $this->assertEquals($currentUrl, $destination);
  }

  /**
   * Data provider for testDestinationParameterOverride.
   */
  public function destinationParameterOverrideDataProvider() {
    return [
      [
        'admin/config',
        'user/login?customDestination=admin/config',
      ],
      [
        'admin/modules',
        'user/login?customDestination=admin/modules',
      ],
    ];
  }

}
