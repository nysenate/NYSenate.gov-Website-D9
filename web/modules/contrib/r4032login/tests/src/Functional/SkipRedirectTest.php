<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test that is well avoided to redirect for configured urls.
 *
 * @group r4032login
 */
class SkipRedirectTest extends BrowserTestBase {

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
    $config->set('match_noredirect_pages', "/admin/config/*\n/admin/modules");
    $config->save();
  }

  /**
   * Test that is well avoided to redirect for configured urls.
   *
   * @param string $path
   *   Request path.
   * @param int $code
   *   Response status code.
   * @param string $destination
   *   Resulting URL.
   *
   * @dataProvider skipRedirectDataProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSkipRedirect($path, $code, $destination) {
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals($code);
    $this->assertSession()->addressEquals($destination);
  }

  /**
   * Data provider for testSkipRedirect.
   */
  public function skipRedirectDataProvider() {
    return [
      [
        'admin/config/development',
        403,
        'admin/config/development',
      ],
      [
        'admin/config',
        200,
        'user/login',
      ],
      [
        'admin/modules',
        403,
        'admin/modules',
      ],
      [
        'admin/modules/uninstall',
        200,
        'user/login',
      ],
    ];
  }

}
