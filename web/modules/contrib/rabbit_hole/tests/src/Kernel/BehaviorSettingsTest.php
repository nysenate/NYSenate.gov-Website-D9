<?php

namespace Drupal\Tests\rabbit_hole\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test cases for BehaviorSettings.
 *
 * @coversDefaultClass \Drupal\rabbit_hole\Entity\BehaviorSettings
 * @group rabbit_hole
 */
class BehaviorSettingsTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rabbit_hole',
    'system',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['rabbit_hole']);

    \Drupal::service('rabbit_hole.behavior_settings_manager')->enableEntityType('node');

    NodeType::create([
      'type' => 'article',
      'name' => 'article',
    ])->save();
  }

  /**
   * @covers ::loadByEntityTypeBundle()
   */
  public function testLoadByEntityTypeBundle() {
    $config = BehaviorSettings::loadByEntityTypeBundle('node', 'article');
    // Config is not available yet.
    $this->assertTrue($config->isNew());

    $config->setAction('page_not_found')
      ->save();

    // Verify that config object was saved.
    $config = BehaviorSettings::loadByEntityTypeBundle('node', 'article');
    $this->assertFalse($config->isNew());
    $this->assertEquals('page_not_found', $config->getAction());

    // Entity type is not available.
    $this->expectException(\InvalidArgumentException::class);
    BehaviorSettings::loadByEntityTypeBundle('taxonomy_term', 'tags');
  }

  /**
   * @covers ::preCreate()
   */
  public function testDefaultValues() {
    $config = BehaviorSettings::loadByEntityTypeBundle('node', 'article');
    $this->assertTrue($config->isNew());
    $this->assertEquals('display_page', $config->getAction());
    $this->assertFalse($config->getNoBypass());
    $this->assertFalse($config->getBypassMessage());
    $this->assertEmpty($config->getConfiguration());
  }

}
