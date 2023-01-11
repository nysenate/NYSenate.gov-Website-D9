<?php

namespace Drupal\Tests\login_security\Functional;

/**
 * Test Login Security's web interface.
 *
 * @group login_security
 */
class LoginSecurityInterfaceTest extends LoginSecurityTestBase {

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and login admin user:
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test admin user settings.
   */
  public function testAdminUserSettings() {
    $settings_fields = $this->getAdminUserSettingsFields();

    $this->drupalGet(parent::ADMIN_SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(200);

    // Assert Fields.
    foreach ($settings_fields as $field_name) {
      $this->assertSession()->fieldExists($field_name);
    }
  }

}
