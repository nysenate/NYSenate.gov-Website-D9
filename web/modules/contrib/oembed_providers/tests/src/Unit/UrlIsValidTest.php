<?php

namespace Drupal\Tests\oembed_providers\Unit;

use Drupal\oembed_providers\OembedProviderForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the urlIsValid method.
 *
 * This class is adapted from \Drupal\Tests\Component\Utility\UrlHelperTest.
 *
 * @group oembed_providers
 *
 * @covers \Drupal\oembed_providers\OembedProviderForm::urlIsValid
 */
class UrlIsValidTest extends UnitTestCase {

  /**
   * The oEmbed Providers provider form object.
   *
   * @var \Drupal\oembed_providers\OembedProviderForm
   */
  protected $formObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $messenger = $this->getMockBuilder('Drupal\Core\Messenger\Messenger')
      ->disableOriginalConstructor()
      ->getMock();
    $this->formObject = new OembedProviderForm($entity_type_manager, $messenger);
  }

  /**
   * Data provider for testValidAbsolute().
   *
   * @return array
   *   An array of test values.
   */
  public function providerTestValidAbsoluteData() {
    $urls = [
      'example.com/asset/*',
      'www.example.com/asset/*',
      'www.example.com/asset/*',
      '*.example.com/asset/*',
      '*.example.com:8080/asset/*',
      'example.com:8080/asset/?id=*',
      '*.example.com:8080/asset/?id=*',
      '127.0.0.1',
      '127.0.0.1:8085',
      '127.0.0.1:8085/*',
      '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]/path/*',
      '[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:8085/path/*',
    ];
    return $this
      ->dataEnhanceWithScheme($urls);
  }

  /**
   * Tests valid absolute URLs.
   *
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   *
   * @dataProvider providerTestValidAbsoluteData
   */
  public function testValidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = $this->formObject->urlIsValid($test_url);
    $this
      ->assertTrue($valid_url, $test_url . ' is a valid URL.');
  }

  /**
   * Provides data for testInvalidAbsolute().
   *
   * @return array
   *   An array of test values.
   */
  public function providerTestInvalidAbsolute() {
    $data = [
      '',
      'ex!ample.com',
      'ex%ample.com',
      '*.*.example.com',
      'sub.*.example.com',
      '*.com',
      '*.com/path/*',
    ];
    return $this
      ->dataEnhanceWithScheme($data);
  }

  /**
   * Tests invalid absolute URLs.
   *
   * @param string $url
   *   The url to test.
   * @param string $scheme
   *   The scheme to test.
   *
   * @dataProvider providerTestInvalidAbsolute
   */
  public function testInvalidAbsolute($url, $scheme) {
    $test_url = $scheme . '://' . $url;
    $valid_url = $this->formObject->urlIsValid($test_url);
    $this
      ->assertFalse($valid_url, $test_url . ' is NOT a valid URL.');
  }

  /**
   * Enhances test urls with schemes.
   *
   * @param array $urls
   *   The list of urls.
   *
   * @return array
   *   A list of provider data with schemes.
   */
  protected function dataEnhanceWithScheme(array $urls) {
    $url_schemes = [
      'http',
      'https',
    ];
    $data = [];
    foreach ($url_schemes as $scheme) {
      foreach ($urls as $url) {
        $data[] = [
          $url,
          $scheme,
        ];
      }
    }
    return $data;
  }

}
