<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests password reset behaviors.
 *
 * @group password_policy
 */
class PasswordResetBehaviorsTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  use CronRunTrait;

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
    'file',
    'image',
    'options',
    'text',
    'field_ui',
    'password_policy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

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
  }

  /**
   * Test password reset behaviors.
   */
  public function testPasswordResetBehaviors() {

    // Create user with permission to create policy.
    // Below causes a custom role to be created that has no entity storage.
    // This makes the CMI layer barf and changing CMI fail.
    $user1 = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
      'administer permissions',
      'manage password reset',
      'administer account settings',
      'administer user fields',
      'administer user form display',
      'access administration pages',
    ]);

    $this->drupalLogin($user1);

    // Assert that user attributes were created and unexpired.
    $user_instance = User::load($user1->id());
    $this->assertNotNull($user_instance->get('field_last_password_reset')[0]->value, 'Last password reset was not set on user add');
    self::assertEquals($user_instance->get('field_password_expiration')[0]->value, '0', 'Password expiration field is not set to zero on user add');

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
    /** @var \Drupal\user\UserInterface $user2 */
    $user2 = array_shift($user_array);

    // Edit the user password reset date.
    $this->drupalGet('user/' . $user2->id() . '/edit');
    $edit = [
      'field_last_password_reset[0][value][date]' => date('Y-m-d', strtotime('-90 days')),
    ];
    $this->submitForm($edit, 'Save');

    // Create new password reset policy for role.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
      'password_reset' => '1',
    ];
    // Set reset and policy info.
    $this->submitForm($edit, 'Save');
    // Set the roles for the policy.
    $edit = [
      'roles[' . $rid . ']' => $rid,
    ];
    $this->submitForm($edit, 'Save');

    // Time to kick this popsicle stand.
    $this->drupalLogout();

    // Run cron to trigger expiration.
    $this->cronRun();

    // User should be redirected to the user entity edit page after login.
    $this->drupalGet('user/login');
    $this->submitForm(['name' => 'testuser1', 'pass' => 'pass'], 'Log in');
    $this->assertSession()->addressEquals($user2->toUrl('edit-form'));
    $this->drupalLogout();

    // Create a new node type.
    $type1 = $this->drupalCreateContentType();
    // Create a node of that type.
    $node_title = $this->randomMachineName();
    $node_body = $this->randomMachineName();
    $edit = [
      'type' => $type1->get('type'),
      'title' => $node_title,
      'body' => [['value' => $node_body]],
      'langcode' => 'en',
    ];
    $node = $this->drupalCreateNode($edit);

    // Verify if user tries to go to node, they are forced back.
    $this->drupalGet('user/login');
    $this->submitForm(['name' => 'testuser1', 'pass' => 'pass'], 'Log in');
    $this->drupalGet($node->toUrl()->toString());
    $this->assertSession()->addressEquals($user2->toUrl('edit-form')->setAbsolute()->toString());

    // Change password.
    $this->drupalGet('user/' . $user2->id() . '/edit');
    $edit = [];
    $edit['pass[pass1]'] = '1';
    $edit['pass[pass2]'] = '1';
    $edit['current_pass'] = 'pass';
    $this->submitForm($edit, 'Save');

    // Verify expiration is unset.
    $user_instance = User::load($user2->id());
    self::assertEquals($user_instance->get('field_password_expiration')[0]->value, '0', 'Password expiration field should be empty after changing password');

    // Verify if user tries to go to node, they are allowed.
    $this->drupalGet($node->toUrl()->toString());
    $this->assertSession()->addressEquals($node->toUrl()->setAbsolute(TRUE)->toString());

    // Test submitting a node form while expired.
    $this->grantPermissions(Role::load($rid), [
      'create ' . $type1->id() . ' content',
    ]);
    $this->drupalGet('node/add/' . $type1->id());
    $this->assertSession()->statusCodeEquals(200);

    // Simulate the user being expired via cron.
    $user2->set('field_password_expiration', 1)->save();

    $this->submitForm([
      'title[0][value]' => 'Test creating content while expired',
    ], 'Save');
    $this->assertSession()->pageTextContains($type1->label() . ' Test creating content while expired has been created.');
    // User will still be redirected, but not during the POST request.
    $this->assertSession()->addressEquals($user2->toUrl('edit-form')->setAbsolute()->toString());
  }

}
