<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test that query string is preserved after redirection.
 *
 * @group r4032login
 */
class PreserveQueryStringTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['r4032login'];

  /**
   * Tests query string preservation.
   *
   * @param string $path
   *   Request path.
   * @param array $options
   *   Request options.
   * @param int $code
   *   Response status code.
   * @param string $destination
   *   Resulting URL.
   *
   * @dataProvider preserveQueryStringDataProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPreserveQueryString($path, array $options, $code, $destination) {
    $this->drupalGet($path, $options);
    $this->assertSession()->statusCodeEquals($code);

    $currentUrl = str_replace($this->baseUrl . '/', '', $this->getUrl());
    $this->assertEquals($currentUrl, $destination);
  }

  /**
   * Data provider for testPreserveQueryString.
   */
  public function preserveQueryStringDataProvider() {
    return [
      [
        'admin/modules',
        [],
        200,
        'user/login?destination=admin/modules',
      ],
      [
        'admin/modules',
        [
          'query' => [
            'foo' => 'bar',
          ],
        ],
        200,
        'user/login?destination=admin/modules%3Ffoo%3Dbar',
      ],
      [
        'admin',
        [
          'query' => [
            'destination' => 'admin/modules',
          ],
        ],
        200,
        'user/login?destination=admin%3Fdestination%3Dadmin%252Fmodules',
      ],
    ];
  }

}
