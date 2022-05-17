<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\oembed_providers\Entity\ProviderBucket;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the provider bucket config entity type.
 *
 * @group oembed_providers
 */
class ProviderBucketTest extends BrowserTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test non-administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'oembed_providers',
    'oembed_providers_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer oembed providers',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);
  }

  /**
   * Tests route permissions.
   */
  public function testRoutePermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    // Non-admin user is unable to access Provider Bucket listing page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Provider Bucket add page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/add');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Provider Bucket edit page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/test_bucket/edit');
    $assert_session->statusCodeEquals(403);
    // Non-admin user is unable to access Provider Bucket delete page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/test_bucket/delete');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    // Admin user is able to access Provider Bucket listing page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Provider Bucket add page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/add');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Provider Bucket edit page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/test_bucket/edit');
    $assert_session->statusCodeEquals(200);
    // Admin user is able to access Provider Bucket delete page.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/test_bucket/delete');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Tests Custom Providers add/edit form.
   */
  public function testProviderBucketForm() {
    $this->useFixtureProviders();
    $this->lockHttpClientToFixtures();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/media/oembed-providers/buckets/add');

    $page
      ->findField('label')
      ->setValue('My Test Bucket');
    $page
      ->findField('id')
      ->setValue('my_test_bucket');
    $page
      ->findField('description')
      ->setValue('A Description of My Test Bucket');

    $page->findField('providers[Vimeo]')->check();
    $page->pressButton('Save');

    $assert_session->pageTextContains('The My Test Bucket oEmbed provider bucket was created.');

    // Verify storage and retrieval.
    $this->drupalGet('/admin/config/media/oembed-providers/buckets/my_test_bucket/edit');

    $value = $page
      ->findField('label')
      ->getValue();
    $this->AssertSame($value, 'My Test Bucket');

    $value = $page
      ->findField('id')
      ->getValue();
    $this->AssertSame($value, 'my_test_bucket');

    $value = $page
      ->findField('description')
      ->getValue();
    $this->AssertSame($value, 'A Description of My Test Bucket');

    $assert_session->checkboxChecked('Vimeo');
    $assert_session->checkboxNotChecked('YouTube');

    // Verify media source is registered.
    // There's no need to separately verify the config entity.
    $media_sources = \Drupal::service('plugin.manager.media.source')->getDefinitions();
    $this->assertArrayHasKey('oembed:my_test_bucket', $media_sources);

    $this->assertEquals($media_sources['oembed:my_test_bucket']['label'], 'My Test Bucket');
    $this->assertEquals($media_sources['oembed:my_test_bucket']['id'], 'my_test_bucket');
    $this->assertEquals($media_sources['oembed:my_test_bucket']['description'], 'A Description of My Test Bucket');
    $providers = $media_sources['oembed:my_test_bucket']['providers'];
    $this->AssertTrue(in_array('Vimeo', $providers));
    $this->AssertFalse(in_array('YouTube', $providers));
  }

  /**
   * Tests dependency calculation for ProviderBucket entities.
   */
  public function testProviderBucketDependencyCalculation() {
    // Create a test provider bucket with a custom provider.
    $provider_bucket = ProviderBucket::create([
      'id' => 'my_test_bucket',
      'label' => 'My Test Bucket',
      'descriptions' => 'A Description of My Test Bucket',
      'providers' => [
        'Example Provider',
      ],
    ]);
    $provider_bucket->save();

    $expected = [
      'config' => [
        'oembed_providers.provider.example_provider',
      ],
    ];
    $this->AssertSame($expected, $provider_bucket->getDependencies());
  }

}
