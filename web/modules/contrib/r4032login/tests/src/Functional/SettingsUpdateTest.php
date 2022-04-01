<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test update of settings from admin/config/system/site-information.
 *
 * @group r4032login
 */
class SettingsUpdateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['r4032login'];

  /**
   * Test update of settings.
   *
   * @param array $permissions
   *   The permissions for the user to test against.
   * @param bool $admin
   *   Either or not the user to test against is an admin.
   *
   * @dataProvider settingsUpdateDataProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSettingsUpdate(array $permissions, $admin) {
    $webUser = $this->drupalCreateUser($permissions, NULL, $admin);

    $this->drupalLogin($webUser);

    $settings = [
      'r4032login_redirect_to_destination' => FALSE,
      'r4032login_display_denied_message' => FALSE,
      'r4032login_access_denied_message' => 'Access denied',
      'r4032login_access_denied_message_type' => 'status',
      'r4032login_redirect_authenticated_users_to' => 'https://www.drupal.org',
      'r4032login_user_login_path' => '/user/login',
      'r4032login_default_redirect_code' => 302,
      'r4032login_destination_parameter_override' => 'test',
      'r4032login_match_noredirect_pages' => '/admin/config',
    ];
    $edit = array_merge($settings, [
      'site_frontpage' => '/admin/config/system/site-information',
    ]);

    // The submission should fail because the /abcd path is invalid.
    $edit['r4032login_user_login_path'] = '/abcd';
    $this->drupalPostForm('admin/config/system/site-information', $edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains("The user login form path '/abcd' is either invalid or a logged out user does not have access to it.");

    // This submission should fail because
    // the /admin/config/system/site-information path is not accessible
    // to anonymous.
    $edit['r4032login_user_login_path'] = '/admin/config/system/site-information';
    $this->drupalPostForm('admin/config/system/site-information', $edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains("The user login form path '/admin/config/system/site-information' is either invalid or a logged out user does not have access to it.");

    // This submission should success
    // because the external https://www.drupal.org/user/login path is valid
    // for anonymous.
    $edit['r4032login_user_login_path'] = 'https://www.drupal.org/user/login';
    $this->drupalPostForm('admin/config/system/site-information', $edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved.');

    // This submission should success
    // because the internal /user/login path is valid for anonymous.
    $edit['r4032login_user_login_path'] = '/user/login';
    $this->drupalPostForm('admin/config/system/site-information', $edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved.');

    // Test that settings were correctly updated.
    $config = $this->config('r4032login.settings');
    foreach ($settings as $key => $value) {
      $key = str_replace('r4032login_', '', $key);
      $this->assertEqual($config->get($key), $value);
    }
  }

  /**
   * Data provider for testSettingsUpdate.
   */
  public function settingsUpdateDataProvider() {
    return [
      [
        [],
        TRUE,
      ],
      [
        ['administer site configuration'],
        FALSE,
      ],
    ];
  }

}
