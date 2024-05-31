<?php

namespace Drupal\Tests\password_policy\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests password policy migration for 7.x-1.x.
 *
 * @group password_policy
 */
class PasswordPolicy71MigrateTest extends MigrateDrupal7TestBase {

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
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('password_policy'),
      'tests',
      'fixtures',
      'drupal7.1.php',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'password_policy',
    ]);
    $this->installEntitySchema('password_policy');
    $this->installConfig([
      'password_policy',
    ]);
    $this->installEntitySchema('password_policy');
  }

  /**
   * Asserts that password policies are migrated.
   */
  public function testPasswordPolicyMigration() {
    $expected_config = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'password_policy_characters',
        ],
      ],
      'id' => 'password_policy_example_d7',
      'label' => 'Password policy Example D7',
      'password_reset' => 10,
      'policy_constraints' => [
        0 => [
          'id' => 'password_policy_character_constraint',
          'character_count' => 2,
          'character_type' => 'uppercase',
        ],
        1 => [
          'id' => 'password_policy_character_constraint',
          'character_count' => 10,
          'character_type' => 'numeric',
        ],
      ],
      'send_reset_email' => TRUE,
      'send_pending_email' => [
        0 => 5,
        1 => 9,
      ],
      'roles' => [
        'authenticated' => 'authenticated',
        'administrator' => 'administrator',
        'new_role' => 'new_role',
      ],
      'show_policy_table' => TRUE,
    ];
    $migrations = [
      'd7_user_role',
      'password_policy_settings',
    ];
    $this->executeMigrations($migrations);
    $actual_config = $this->config('password_policy.password_policy.password_policy_example_d7')->getRawData();
    $this->assertEquals(
      $expected_config,
      array_diff_key(
        $actual_config,
        ['uuid' => 'uuid']
      ),
    );
    // Test for policy 2.
    $expected_config_for_policy2 = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'password_policy_characters',
        ],
      ],
      'id' => 'password_policy_example_2_d7',
      'label' => 'Password policy Example 2  D7',
      'password_reset' => 0,
      'send_reset_email' => TRUE,
      'send_pending_email' => [
        0 => 3,
      ],
      'policy_constraints' => [
        0 => [
          'id' => 'password_policy_character_constraint',
          'character_count' => 3,
          'character_type' => 'uppercase',
        ],
        1 => [
          'id' => 'password_policy_character_constraint',
          'character_count' => 11,
          'character_type' => 'numeric',
        ],
      ],
      'roles' => [
        'administrator' => 'administrator',
      ],
      'show_policy_table' => TRUE,
    ];
    $actual_config_for_policy2 = $this->config('password_policy.password_policy.password_policy_example_2_d7')->getRawData();
    $this->assertEquals(
      $expected_config_for_policy2,
      array_diff_key(
        $actual_config_for_policy2,
        ['uuid' => 'uuid']
      ),
    );

  }

}
