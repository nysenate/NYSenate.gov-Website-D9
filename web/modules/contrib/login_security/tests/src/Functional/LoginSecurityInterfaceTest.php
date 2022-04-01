<?php

namespace Drupal\Tests\login_security\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Test Login Security's web interface.
 *
 * @group login_security
 */
class LoginSecurityInterfaceTest extends LoginSecurityTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create and login user.
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test admin user settings.
   */
  public function testAdminUserSettings() {
    $settings_fields = $this->getAdminUserSettingsFields();

    $this->drupalGet(parent::ADMIN_SETTINGS_PATH);
    $this->assertResponse(200, 'Access granted to settings page.');

    // Assert Fields.
    foreach ($settings_fields as $field_name) {
      $this->assertField($field_name, new FormattableMarkup('@field_name field exists.', ['@field_name' => $field_name]));
    }
  }

}
