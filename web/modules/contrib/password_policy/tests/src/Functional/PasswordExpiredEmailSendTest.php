<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\Entity\User;

/**
 * Tests password reset behaviors.
 *
 * @group password_policy
 */
class PasswordExpiredEmailSendTest extends BrowserTestBase {

  use CronRunTrait;
  use AssertMailTrait;

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with some administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  private $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'password_policy',
    'mail_html_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set module install date far in the past so it does not affect regular
    // password expiration behaviors tested here.
    /* @see password_policy_install */
    $timestamp = \Drupal::service("date.formatter")->format(
      0,
      'custom',
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      DateTimeItemInterface::STORAGE_TIMEZONE
    );
    \Drupal::state()->set('password_policy.install_time', $timestamp);
    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
      'manage password reset',
      'administer account settings',
      'access administration pages',
    ]);
  }

  /**
   * Test password reset behaviors.
   */
  public function testPasswordResetBehaviors() {

    $this->drupalLogin($this->adminUser);

    // Assert that user attributes were created and unexpired.
    $user_instance = User::load($this->adminUser->id());
    $this->assertNotNull($user_instance->get('field_last_password_reset')[0]->value, 'Last password reset was not set on user add');
    self::assertEquals($user_instance->get('field_password_expiration')[0]->value, '0', 'Password expiration field is not set to zero on user add');
    self::assertEquals($user_instance->get('field_pending_expire_sent')[0]->value, '0', 'Password pensing expire sent field is not set to zero on user add');

    // Create a new role.
    $rid = $this->drupalCreateRole([]);

    // Create user with test role.
    $this->drupalGet('admin/people/create');
    $edit = [
      'roles[' . $rid . ']' => $rid,
      'mail' => 'example12@example.com',
      'name' => 'testuser1',
      'pass[pass1]' => 'pass',
      'pass[pass2]' => 'pass',
    ];
    $this->submitForm($edit, 'Create new account');

    // Grab the user info.
    $user_array = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'testuser1']);
    $user2 = array_shift($user_array);

    // Edit the user password reset date.
    $this->drupalGet('user/' . $user2->id() . '/edit');
    $edit = [
      'field_last_password_reset[0][value][date]' => date('Y-m-d', strtotime('-5 days')),
    ];
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '10',
      'send_reset_email' => TRUE,
      'send_pending_email' => '1,5',
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Save');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->drupalGet('admin/config/security/password-policy/test');
    $this->submitForm($edit, 'Save');

    // Time to kick this popsicle stand.
    $this->drupalLogout();

    // Run cron to trigger expiration.
    $this->cronRun();

    $link = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();
    $days_left = 5;
    // Assert mail content.
    $this->assertMailString('body', "Your password will expire in less than $days_left days. Please visit the following\n link to reset your password: $link", 1);

    $this->drupalLogin($this->adminUser);
    // Set the user reset date to one day before expiration date.
    $this->drupalGet('user/' . $user2->id() . '/edit');
    $edit = [
      'field_last_password_reset[0][value][date]' => date('Y-m-d', strtotime('-9 days')),
    ];
    $this->submitForm($edit, 'Save');

    // Run cron to trigger expiration.
    $this->cronRun();

    $link = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();
    $days_left = 1;
    // Assert mail content.
    $this->assertMailString('body', "Your password will expire in less than $days_left days. Please visit the following\n link to reset your password: $link", 1);
  }

}
