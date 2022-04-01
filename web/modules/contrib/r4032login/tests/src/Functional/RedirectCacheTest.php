<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;

/**
 * Test caching redirection.
 *
 * @group r4032login
 */
class RedirectCacheTest extends BrowserTestBase {

  use FileFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'node',
    'page_cache',
    'r4032login',
  ];

  /**
   * The node used for tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The file used for tests.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Disable the access denied message so the cache will be set.
    $config = $this->config('r4032login.settings');
    $config->set('display_denied_message', FALSE);
    $config->save();

    // Create a node type with a private file field.
    $nodeType = NodeType::create(['type' => 'page', 'name' => 'Basic page']);
    $nodeType->save();
    $this->createFileField('field_text_file', 'node', 'page', ['uri_scheme' => 'private']);

    // Create an unpublished node with a private file to test.
    $this->node = $this->drupalCreateNode();
    file_put_contents('private://test.txt', 'test');
    $this->file = File::create([
      'uri' => 'private://test.txt',
      'filename' => 'test.txt',
    ]);
    $this->file->save();
    $this->node->set('field_text_file', $this->file->id());
    $this->node->setUnpublished()->save();
  }

  /**
   * Test node access redirect behavior in cached context.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNodeRedirectCache() {
    // Assert there is the redirection since the node is not published.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->addressEquals('user/login');

    // Publish the node.
    $this->node->setPublished()->save();

    // Assert there is not the redirection since the node is published.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->addressEquals('node/' . $this->node->id());
  }

  /**
   * Tests private file redirect behavior in cached context.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testPrivateFileRedirectCache() {
    // Assert there is the redirection since the node is not published.
    $this->drupalGet(file_create_url($this->file->getFileUri()));
    $this->assertSession()->addressEquals('user/login');

    // Publish the node.
    $this->node->setPublished()->save();

    // Assert there is not the redirection since the node is published.
    $this->drupalGet(file_create_url($this->file->getFileUri()));
    $this->assertSession()
      ->addressEquals(file_create_url($this->file->getFileUri()));
  }

}
