<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\media\OEmbed\ProviderException;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;

/**
 * Tests the oEmbed provider repository.
 *
 * Adapted from Drupal\Tests\media\Functional\ProviderRepositoryTest.
 *
 * @covers \Drupal\oembed_providers\OEmbed\ProviderRepository
 *
 * @group oembed_providers
 */
class ProviderRepositoryTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'oembed_providers',
  ];

  /**
   * Tests that provider discovery fails if the provider database is empty.
   *
   * @param string $content
   *   The expected JSON content of the provider database.
   *
   * @dataProvider providerEmptyProviderList
   */
  public function testEmptyProviderList($content) {
    $response = $this->prophesize('\GuzzleHttp\Psr7\Response');
    $response->getBody()->willReturn($content);

    $client = $this->createMock('\GuzzleHttp\Client');
    $client->method('request')->withAnyParameters()->willReturn($response->reveal());
    $this->container->set('http_client', $client);

    $this->expectException(ProviderException::class);
    $this->expectExceptionMessage('Remote oEmbed providers database returned invalid or empty list.');
    $this->container->get('media.oembed.provider_repository')->getAll();
  }

  /**
   * Data provider for testEmptyProviderList().
   *
   * @see ::testEmptyProviderList()
   *
   * @return array
   *   An array of values to test.
   */
  public function providerEmptyProviderList() {
    return [
      'empty array' => ['[]'],
      'empty string' => [''],
    ];
  }

  /**
   * Tests that provider discovery fails with a non-existent provider database.
   *
   * @param string $providers_url
   *   The URL of the provider database.
   * @param string $exception_message
   *   The expected exception message.
   *
   * @dataProvider providerNonExistingProviderDatabase
   */
  public function testNonExistingProviderDatabase($providers_url, $exception_message) {
    $this->config('media.settings')
      ->set('oembed_providers_url', $providers_url)
      ->save();

    // Set up a mock handler.
    // Core test requires calls to external service.
    $mock = new MockHandler([
      new RequestException('Error Communicating with Server', new Request('GET', 'test')),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $this->container->set('http_client', $client);

    $this->expectException(ProviderException::class);
    $this->expectExceptionMessage($exception_message);
    $this->container->get('media.oembed.provider_repository')->getAll();
  }

  /**
   * Data provider for testEmptyProviderList().
   *
   * @see ::testEmptyProviderList()
   *
   * @return array
   *   An array of values to test.
   */
  public function providerNonExistingProviderDatabase() {
    return [
      [
        'http://oembed1.com/providers.json',
        'Could not retrieve the oEmbed provider database from http://oembed1.com/providers.json',
      ],
      [
        'http://oembed.com/providers1.json',
        'Could not retrieve the oEmbed provider database from http://oembed.com/providers1.json',
      ],
    ];
  }

  /**
   * Tests that hook_oembed_providers_alter() is invoked.
   */
  public function testProvidersAlterHook() {
    $this->container->get('module_installer')->install(['oembed_providers_test']);
    $providers = $this->container->get('media.oembed.provider_repository')->getAll();
    $this->assertArrayHasKey('My Custom Provider', $providers);
  }

}
