<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the 'migmag_rollbackable_shortcut_set_users' destination.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableShortcutSetUsers
 *
 * @group migmag_rollbackable
 */
class RollbackableShortcutSetUsersTest extends RollbackableDestinationTestBase {

  use UserCreationTrait;

  /**
   * Base definition for the test migrations.
   *
   * @const array
   */
  const USER_SHORTCUT_SET_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'uid' => 22,
          'set_name' => 'shortcut-set-22-base',
        ],
      ],
      'ids' => [
        'uid' => ['type' => 'integer'],
        'set_name' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'uid' => 'uid',
      'set_name' => 'set_name',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_shortcut_set_users',
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * Access level should be public for Drupal core 8.9.x.
   */
  public static $modules = [
    'link',
    'shortcut',
  ];

  /**
   * The user whose shortcut set migration is tested.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->testUser = $this->createUser([], 'test_user', FALSE, ['uid' => 22]);

    $this->installEntitySchema('shortcut');
    $this->installSchema('shortcut', ['shortcut_set_users']);
    $this->installConfig(['shortcut']);

    ShortcutSet::create([
      'id' => 'shortcut-set-22-init',
      'label' => 'Initial shortcut set of user 22',
    ])->save();
    ShortcutSet::create([
      'id' => 'shortcut-set-22-base',
      'label' => 'Base shortcut set of user 22',
    ])->save();
    ShortcutSet::create([
      'id' => 'shortcut-set-22-subsequent',
      'label' => 'Subsequent shortcut set of user 22',
    ])->save();
  }

  /**
   * Tests the rollbackability of 'rollbackable_shortcut_set_users' destination.
   */
  public function testShortcutSetUsersRollback() {
    $this->assertEquals('default', shortcut_current_displayed_set($this->testUser)->id());

    // Import...
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    // Check the shortcut set assigned to the test user after the base
    // migration was executed.
    $this->assertEquals('shortcut-set-22-base', shortcut_current_displayed_set($this->testUser)->id());

    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    // Check the shortcut set assigned to the test user after the subsequent
    // migration was executed.
    $this->assertEquals('shortcut-set-22-subsequent', shortcut_current_displayed_set($this->testUser)->id());

    $subsequent_executable->rollback();

    // Check the shortcut set assigned to the test user after the subsequent
    // migration was rolled back.
    $this->assertEquals('shortcut-set-22-base', shortcut_current_displayed_set($this->testUser)->id());

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals('default', shortcut_current_displayed_set($this->testUser)->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration() {
    $definition = ['id' => 'user_shortcut_set_base'] + self::USER_SHORTCUT_SET_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration() {
    $definition = self::USER_SHORTCUT_SET_MIGRATION_BASE;
    $definition['id'] = 'user_shortcut_set_base_subsequent';
    $definition['source']['data_rows'] = [
      [
        'uid' => 22,
        'set_name' => 'shortcut-set-22-subsequent',
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration() {
    // User shortcut set destination cannot have translation destination.
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration() {
    // User shortcut set destination cannot have translation destination.
  }

}
