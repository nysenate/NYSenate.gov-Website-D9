<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\password_policy\Entity\PasswordPolicy;
use Drupal\user\Entity\User;

/**
 * Tests password reset behaviors after initial install.
 *
 * @group password_policy
 */
class InitialInstallBehaviorsTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'dblog',
    'config',
    'field',
    'datetime',
  ];

  /**
   * Test password reset behaviors.
   */
  public function testInitialInstallBehaviors() {
    // Create a regular user.
    $user = $this->drupalCreateUser();

    // Install Password Policy module.
    $this->container->get('module_installer')->install(['password_policy']);

    // Assert "password_policy.install_time" state variable was set.
    $this->assertNotNull(\Drupal::state()->get('password_policy.install_time'), 'Install time state variable not set during install.');

    // Assert that existing user attributes were _not_ changed by install
    // process.
    $user_instance = User::load($user->id());
    $this->assertNull($user_instance->get('field_last_password_reset')->value, 'Existing user last password reset was set on module install.');
    $this->assertNull($user_instance->get('field_password_expiration')->value, 'Existing user password expiration field was set on module install.');

    // Create password reset policy with one day expiration.
    $policy = PasswordPolicy::create([
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
      'roles' => ['authenticated'],
    ]);
    $policy->save();

    // Run cron.
    \Drupal::service('cron')->run();

    // Assert that existing user attributes remain unchanged by the cron run.
    $user_instance = User::load($user->id());
    $this->assertNull($user_instance->get('field_last_password_reset')->value, 'Existing user last password reset was set by cron run.');
    $this->assertNull($user_instance->get('field_password_expiration')->value, 'Existing user password expiration field was set by cron run.');

    // Update install time state value to an older time.
    $timestamp = \Drupal::service("date.formatter")->format(
      0,
      'custom',
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      DateTimeItemInterface::STORAGE_TIMEZONE
    );
    \Drupal::state()->set('password_policy.install_time', $timestamp);

    // Run cron again.
    \Drupal::service('cron')->run();

    // Assert that user's password has been expired.
    $user_instance = User::load($user->id());
    $this->assertEquals($user_instance->get('field_password_expiration')->value, 1, 'Password not reset by cron run.');
  }

}
