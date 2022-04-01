<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the option "Redirect user to the page they tried to access after login".
 *
 * @group r4032login
 */
class RedirectToDestinationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['r4032login'];

  /**
   * Tests the behavior of the redirect_to_destination option.
   *
   * @param bool $optionValue
   *   The option value for "redirect_to_destination".
   * @param string $path
   *   Request path.
   * @param string $destination
   *   Resulting URL.
   *
   * @dataProvider redirectToDestinationDataProvider
   */
  public function testRedirectToDestination($optionValue, $path, $destination) {
    $config = $this->config('r4032login.settings');
    $config->set('redirect_to_destination', $optionValue);
    $config->save();

    $this->drupalGet($path);

    $currentUrl = str_replace($this->baseUrl . '/', '', $this->getUrl());
    $this->assertEquals($currentUrl, $destination);
  }

  /**
   * Data provider for testRedirectToDestination.
   */
  public function redirectToDestinationDataProvider() {
    return [
      [
        TRUE,
        'admin/config',
        'user/login?destination=admin/config',
      ],
      [
        FALSE,
        'admin/config',
        'user/login',
      ],
      [
        TRUE,
        'admin/modules',
        'user/login?destination=admin/modules',
      ],
      [
        FALSE,
        'admin/modules',
        'user/login',
      ],
    ];
  }

}
