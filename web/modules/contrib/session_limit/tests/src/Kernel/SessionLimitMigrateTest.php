<?php

namespace Drupal\Tests\session_limit\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests session limit config migration.
 *
 * @group session_limit
 */
class SessionLimitMigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'session_limit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('session_limit'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Asserts that session limit configuration is migrated.
   */
  public function testSessionLimitMigration() {
    $expected_config = [
      'session_limit_max' => 5,
      'session_limit_masquerade_ignore' => TRUE,
      'session_limit_behaviour' => 1,
      'session_limit_logged_out_message_severity' => 'status',
      'session_limit_admin_inclusion' => 0,
      'session_limit_roles' => [
        'authenticated' => 1,
        'administrator' => 2,
        'editor' => 3,
      ],
    ];
    $this->executeMigrations(['d7_user_role', 'session_limit_settings']);
    $config = $this->config('session_limit.settings')->getRawData();
    $this->assertSame($expected_config, $config);
  }

}
