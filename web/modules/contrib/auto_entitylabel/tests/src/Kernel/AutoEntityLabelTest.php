<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests for auto entity label.
 *
 * @group auto_entitylabel
 */
class AutoEntityLabelTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'node',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');

    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);

    $this->installConfig(self::$modules);

    // Create content type.
    $this->nodeType = $this->createContentType(['type' => 'page']);

    \Drupal::configFactory()
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}")
      ->set('status', AutoEntityLabelManager::OPTIONAL)
      ->set('pattern', '[node:author:name]')
      ->save();
  }

  /**
   * Test.
   */
  public function test() {
    $user = $this->createUser();

    // Create node WITH title.
    $title = 'Test Node';
    $node = $this->createNode([
      'title' => 'Test Node',
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($title, $node->getTitle(), 'The title is untouched.');

    // Create node WITHOUT title.
    $node = $this->createNode([
      'title' => '',
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($user->getAccountName(), $node->getTitle(), 'The title is set.');
  }

}
