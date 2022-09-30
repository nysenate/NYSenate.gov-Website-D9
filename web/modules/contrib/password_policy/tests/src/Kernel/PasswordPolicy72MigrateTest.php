<?php

namespace Drupal\Tests\password_policy\Kernel\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests password policy migration for 7.x-2.x.
 *
 * @group password_policy
 */
class PasswordPolicy72MigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'password_policy_character_types',
    'password_policy_characters',
    'password_policy_consecutive',
    'password_policy_history',
    'password_policy_length',
    'password_policy_username',
    'user',
    'text',
    'password_policy',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      \Drupal::service('extension.list.module')->getPath('password_policy'),
      'tests',
      'fixtures',
      'drupal7.2.php',
    ]));
    $this->installConfig([
      'password_policy',
    ]);
    $this->installEntitySchema('password_policy');
    $migrations = [
      'd7_user_role',
      'password_policy_settings',
    ];
    $this->executeMigrations($migrations);
  }

  /**
   * Asserts that password policies are migrated.
   */
  public function testPasswordPolicyMigration() {

    $entityTypeManager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $configStorage */
    $configStorage = $entityTypeManager->getStorage('password_policy');
    $policies = $configStorage->loadMultiple();

    $this->assertCount(1, $policies);

    $expected_test_settings = [
      '0' => [
        'id' => 'password_length',
        'character_length' => 8,
        'character_operation' => 'minimum',
      ],
      '1' => [
        'id' => 'consecutive',
        'max_consecutive_characters' => 3,
      ],
      '2' => [
        'id' => 'password_policy_character_constraint',
        'character_count' => 2,
        'character_type' => 'numeric',
      ],
      '3' => [
        'id' => 'password_policy_character_constraint',
        'character_count' => 6,
        'character_type' => 'letter',
      ],
      '4' => [
        'id' => 'password_policy_character_constraint',
        'character_count' => 1,
        'character_type' => 'special',
      ],
      '5' => [
        'id' => 'password_username',
        'disallow_username' => 1,
      ],
    ];

    /** @var \Drupal\password_policy\Entity\PasswordPolicy $test */
    $test = $configStorage->load('test_policy');
    $this->assertEquals('test_policy', $test->label());
    $this->assertSame(180, $test->getPasswordReset());
    $this->assertTrue($test->getPasswordResetEmailValue());
    $this->assertSame([14, 7, 2], $test->getPasswordPendingValue());
    $this->assertSame(["authenticated" => "authenticated"], $test->getRoles());
    $this->assertEquals($expected_test_settings, $test->getConstraints());
  }

}
