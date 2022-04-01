<?php

namespace Drupal\Tests\name\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the integration with user module.
 *
 * @group name
 */
class NameUserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'name',
    'user',
    'system',
  ];

  /**
   * The entity listener.
   *
   * @var \Drupal\Core\Entity\EntityTypeListener
   */
  protected $entityListener;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installSchema('system', ['sequences']);

    $this->entityListener = \Drupal::service('entity_type.listener');
    $this->entityListener->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('user'));
  }

  /**
   * Tests the user hooks.
   */
  public function testUserHooks() {
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'type' => 'string',
      'entity_type' => 'user',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_text',
      'type' => 'string',
      'entity_type' => 'user',
      'bundle' => 'user',
    ])->save();
    $this->assertIdentical('', \Drupal::config('name.settings')->get('user_preferred'));

    FieldStorageConfig::create([
      'field_name' => 'field_name_test',
      'type' => 'name',
      'entity_type' => 'user',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_name_test2',
      'type' => 'name',
      'entity_type' => 'user',
    ])->save();

    $field = FieldConfig::create([
      'field_name' => 'field_name_test',
      'type' => 'name',
      'entity_type' => 'user',
      'bundle' => 'user',
    ]);
    $field->save();

    $field2 = FieldConfig::create([
      'field_name' => 'field_name_test2',
      'type' => 'name',
      'entity_type' => 'user',
      'bundle' => 'user',
    ]);
    $field2->save();

    $this->assertEquals($field->getName(), \Drupal::config('name.settings')->get('user_preferred'));

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', $field2->getName())
      ->save();

    $field2->delete();
    $this->assertEquals('', \Drupal::config('name.settings')->get('user_preferred'));

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', $field->getName())
      ->save();

    $account = User::create([
      'name' => 'test',
    ]);
    $account->field_name_test[0] = [
      'given' => 'Max',
      'family' => 'Mustermann',
    ];
    $account->save();

    $account = User::load($account->id());
    $this->assertEquals('Max Mustermann', $account->realname);
    $this->assertEquals('Max Mustermann', $account->label());
    $this->assertEquals('test', $account->getAccountName());
    $this->assertEquals('Max Mustermann', $account->getDisplayName());
  }

}
