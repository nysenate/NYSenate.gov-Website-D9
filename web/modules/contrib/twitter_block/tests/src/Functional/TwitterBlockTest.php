<?php

namespace Drupal\Tests\twitter_block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests if the twitter block is available.
 *
 * @group twitter_block
 */
class TwitterBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test', 'block', 'twitter_block'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer site configuration',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the twitter block can be placed and works.
   */
  public function testTwitterBlock() {
    // Test availability of the twitter block in the admin "Place blocks" list.
    \Drupal::service('theme_installer')->install(['olivero', 'claro', 'stark']);
    $theme_settings = $this->config('system.theme');
    foreach (['olivero', 'claro', 'stark'] as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      // Configure and save the block.
      $this->drupalPlaceBlock('twitter_block', [
        'username' => 'drupal',
        'width' => 180,
        'height' => 200,
        'region' => 'content',
        'theme' => $theme,
      ]);
      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $this->assertSession()->pageTextContains('Tweets by @drupal');
    }
  }

}
