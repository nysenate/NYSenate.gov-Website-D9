<?php

namespace Drupal\Tests\oembed_providers\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\oembed_providers\Entity\OembedProvider;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests the UI for custom providers.
 *
 * @group oembed_providers
 */
class CustomProvidersUiTest extends WebDriverTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'media',
    'oembed_providers',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $admin_user = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer blocks',
        'administer oembed providers',
      ]);
    $this->drupalLogin($admin_user);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests Custom Providers add/edit form.
   */
  public function testCustomProviderForm() {
    $this->getSession()->resizeWindow(1200, 2000);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Verify 'Test Provider' doesn't exist in the provider repository.
    $keyed_providers = \Drupal::service('media.oembed.provider_repository')->getAll();
    $this->assertArrayNotHasKey('Test Provider', $keyed_providers);

    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/add');

    $assert_session->pageTextContains('Endpoint #1');

    // Fill in data for Endpoint 1.
    // Cause no validation issues.
    $page
      ->findField('label')
      ->setValue('Test Provider');
    // For some reason the machine name field won't auto-populate, so make
    // it visible and manually populate it.
    $this->assertJsCondition("jQuery('.js-form-type-machine-name').removeClass('visually-hidden')");
    $page
      ->findField('provider_url')
      ->setValue('https://test-provider.com');
    $page
      ->findField('id')
      ->setValue('test_provider');
    $page
      ->findField('endpoints[endpoint-1][schemes]')
      ->setValue('https://test-provider.com/media/*');
    $page
      ->findField('endpoints[endpoint-1][url]')
      ->setValue('https://test-provider.com/oembed/v1/media');
    $page
      ->findField('endpoints[endpoint-1][discovery]')
      ->setValue(1);

    $page->pressButton('Add an endpoint');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Endpoint #2');

    // Fill in data for Endpoint 2.
    // Cause validation issues.
    $page
      ->findField('endpoints[endpoint-2][schemes]')
      ->setValue('invalid URL');
    $page
      ->findField('endpoints[endpoint-2][url]')
      ->setValue('https://test-provider.com/oembed/v1/{invalid}');

    $page->pressButton('Save');

    $assert_session->pageTextContains('A valid URL is required on line 1.');
    $assert_session->pageTextContains('If discovery is disabled, then one or more formats must be explicitly defined for an endpoint.');
    $assert_session->pageTextContains('The URL https://test-provider.com/oembed/v1/{invalid} is not valid.');

    $page->pressButton('Add an endpoint');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Endpoint #3');

    // Fill in data for Endpoint 3.
    // Cause no validation issues.
    $page
      ->findField('endpoints[endpoint-3][schemes]')
      ->setValue('https://test-provider.com/media1/*' . PHP_EOL . 'http://test-provider.com/media1/*' . PHP_EOL . 'http://*.test-provider.com/media1/*');
    $page
      ->findField('endpoints[endpoint-3][url]')
      ->setValue('https://test-provider.com/oembed/v1/{format}');
    $page
      ->findField('endpoints[endpoint-3][formats][json]')
      ->setValue('json');

    // Remove endpoint 2.
    $page->pressButton('remove-endpoint2');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Endpoint #1');
    $assert_session->pageTextNotContains('Endpoint #2');
    $assert_session->pageTextContains('Endpoint #3');

    $page->pressButton('Save');

    $assert_session->pageTextContains("The Test Provider oEmbed provider was created.");

    // Verify cached providers are cleared.
    $this->AssertNull(\Drupal::service('keyvalue')->get('media')->get('oembed_providers'));

    // Verify 'Test Provider' exists in the provider repository.
    $keyed_providers = \Drupal::service('media.oembed.provider_repository')->getAll();
    $this->assertArrayHasKey('Test Provider', $keyed_providers);

    $this->assertInstanceOf(OembedProvider::class, OembedProvider::load('test_provider'));

    // Load resulting config entity and compare to expected values.
    $entity = \Drupal::entityTypeManager()
      ->getStorage('oembed_provider')
      ->load('test_provider');

    $this->AssertSame($entity->get('label'), 'Test Provider');
    $this->AssertSame($entity->get('id'), 'test_provider');
    $this->AssertSame($entity->get('provider_url'), 'https://test-provider.com');

    $endpoints = [
      [
        'schemes' => [
          'https://test-provider.com/media/*',
        ],
        'url' => 'https://test-provider.com/oembed/v1/media',
        'discovery' => TRUE,
        'formats' => [
          'json' => FALSE,
          'xml' => FALSE,
        ],
      ],
      [
        'schemes' => [
          'https://test-provider.com/media1/*',
          'http://test-provider.com/media1/*',
          'http://*.test-provider.com/media1/*',
        ],
        'url' => 'https://test-provider.com/oembed/v1/{format}',
        'discovery' => FALSE,
        'formats' => [
          'json' => TRUE,
          'xml' => FALSE,
        ],
      ],
    ];
    $this->AssertSame($entity->get('endpoints'), $endpoints);

    // Load edit page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/test_provider/edit');
    // Verify re-indexing of endpoints.
    $assert_session->pageTextContains('Endpoint #1');
    $assert_session->pageTextContains('Endpoint #2');
    $value = $page
      ->findField('endpoints[endpoint-1][url]')
      ->getValue();
    $this->AssertSame($value, 'https://test-provider.com/oembed/v1/media');
    $value = $page
      ->findField('endpoints[endpoint-2][url]')
      ->getValue();
    $this->AssertSame($value, 'https://test-provider.com/oembed/v1/{format}');

    // Load delete page.
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/test_provider/delete');

    // Verify deletion process.
    $page->pressButton('Delete');
    $assert_session->pageTextContains('The oembed provider Test Provider has been deleted.');

    // Verify 'Test Provider' doesn't exist in the provider repository.
    $keyed_providers = \Drupal::service('media.oembed.provider_repository')->getAll();
    $this->assertArrayNotHasKey('Test Provider', $keyed_providers);
  }

}
