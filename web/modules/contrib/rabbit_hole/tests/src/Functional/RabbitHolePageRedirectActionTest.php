<?php

namespace Drupal\Tests\rabbit_hole\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the "Page redirect" action.
 *
 * @requires module token
 * @group rabbit_hole
 */
class RabbitHolePageRedirectActionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rabbit_hole', 'media', 'token'];

  /**
   * The behavior settings manager.
   *
   * @var \Drupal\rabbit_hole\BehaviorSettingsManagerInterface
   */
  protected $behaviorSettingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->behaviorSettingsManager = $this->container->get('rabbit_hole.behavior_settings_manager');
    $this->behaviorSettingsManager->enableEntityType('node');

    BehaviorSettings::loadByEntityTypeBundle('node', 'article')
      ->setAction('display_page')
      ->save();
    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', 'article');
  }

  /**
   * Tests redirect action configured on bundle level.
   */
  public function testBundleConfiguration() {
    BehaviorSettings::loadByEntityTypeBundle('node', 'article')
      ->setAction('page_redirect')
      ->setConfiguration([
        'redirect' => '[site:url]',
        'redirect_code' => 301,
        'redirect_fallback_action' => 'access_denied',
      ])
      ->save();

    // Make sure the action is working if it's configured in bundle settings.
    $node = $this->createTestNode();
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(Url::fromRoute('<front>'));
  }

  /**
   * Tests available redirect codes.
   */
  public function testRedirectCodes() {
    $target_entity = $this->createTestNode('display_page');
    $destination_path = $target_entity->toUrl()->toString();

    $this->assertPageRedirect($destination_path, $destination_path, 301);
    $this->assertPageRedirect($destination_path, $destination_path, 302);
    $this->assertPageRedirect($destination_path, $destination_path, 303);
    // TODO: Figure out what should happen on 304 code.
    // $this->assertUrlRedirect(304);.
    $this->assertPageRedirect($destination_path, $destination_path, 305);
    $this->assertPageRedirect($destination_path, $destination_path, 307);
  }

  /**
   * Test available URL patterns.
   */
  public function testRedirectPaths() {
    $test_node = $this->createTestNode();

    $this->assertPageRedirect('/node', '/node');
    $this->assertPageRedirect('https://example.com', 'https://example.com');
    $this->assertPageRedirect('/', '/');
    $this->assertPageRedirect('<front>', '/');
    $this->assertPageRedirect('/<front>', '/');
    $this->assertPageRedirect('internal:/node', '/node');
    $this->assertPageRedirect('entity:node/' . $test_node->id(), $test_node->toUrl());
    $this->assertPageRedirect('base:robots.txt', '/robots.txt');
    $this->assertPageRedirect('route:system.401', '/system/401');
  }

  /**
   * Test URL redirect with token value.
   */
  public function testTokenizedUrlRedirect() {
    // Test redirect with default system token.
    $node = $this->createTestNode('page_redirect', [
      'redirect' => '[site:url]',
      'redirect_code' => 301,
      'redirect_fallback_action' => '',
    ]);

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $expected_url = Url::fromRoute('<front>');
    $this->assertSession()->addressEquals($expected_url);

    // Test more complex scenarios with nested entities.
    // Attach media field to Article content type.
    $storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_related_media',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $storage->save();
    FieldConfig::create([
      'field_storage' => $storage,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Related media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'document' => 'document',
          ],
        ],
      ],
    ])->save();

    $file = $this->createTestFile('first');

    $media = Media::create([
      'bundle' => 'document',
      'name' => $this->randomString(),
      'field_media_document' => $file->id(),
    ]);
    $media->save();

    $node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $media->id(),
      ],
      'rabbit_hole__settings' => [
        'action' => 'page_redirect',
        'settings' => [
          'redirect' => '[node:field_related_media:entity:field_media_document:entity:url]',
          'redirect_code' => 301,
          'redirect_fallback_action' => '',
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());

    if (\Drupal::hasService('file_url_generator')) {
      $expected_url = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($file->getFileUri());
    }
    else {
      // @phpstan-ignore-next-line
      $expected_url = file_create_url($file->getFileUri());
    }
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->responseContains('first');

    // Change the file in media entity and verify that destination changed.
    $file2 = $this->createTestFile('second file');
    $media->set('field_media_document', $file2->id());
    $media->save();

    $this->drupalGet($node->toUrl());

    if (\Drupal::hasService('file_url_generator')) {
      $expected_url = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($file2->getFileUri());
    }
    else {
      // @phpstan-ignore-next-line
      $expected_url = file_create_url($file2->getFileUri());
    }
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->responseContains('second');
  }

  /**
   * Test fallback action behavior.
   */
  public function testFallbackAction() {
    $content_type = $this->drupalCreateContentType([
      'type' => mb_strtolower($this->randomMachineName()),
    ]);
    BehaviorSettings::loadByEntityTypeBundle('node', $content_type->id())
      ->setAction('page_redirect')
      ->setConfiguration([
        'redirect_fallback_action' => 'access_denied',
      ])
      ->save();

    \Drupal::service('rabbit_hole.entity_helper')->createRabbitHoleField('node', $content_type->id());

    // Create a test node with redirect to not-existing field.
    $node1 = $this->drupalCreateNode([
      'type' => $content_type->id(),
      'title' => $this->randomString(),
      'rabbit_hole__settings' => [
        'action' => 'page_redirect',
        'settings' => [
          'redirect' => '[node:field_related_media:entity:field_media_document:entity:url]',
          'redirect_code' => 301,
          'redirect_fallback_action' => '',
        ],
      ],
    ]);

    $this->drupalGet($node1->toUrl());
    // Default fallback action should be "Access Denied".
    $this->assertSession()->addressEquals($node1->toUrl());
    $this->assertSession()->statusCodeEquals(403);

    // Create another test node with redirect to invalid URL value and overriden
    // fallback action.
    $node2 = $this->drupalCreateNode([
      'type' => $content_type->id(),
      'title' => $this->randomString(),
      'rabbit_hole__settings' => [
        'action' => 'page_redirect',
        'settings' => [
          'redirect' => 'invalidscheme:/random',
          'redirect_code' => 301,
          'redirect_fallback_action' => 'page_not_found',
        ],
      ],
    ]);
    $node2->save();
    $this->drupalGet($node2->toUrl());
    $this->assertSession()->addressEquals($node2->toUrl());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test that redirect destination can be altered.
   */
  public function testAlteredRedirectDestination() {
    $this->assertPageRedirect('/node', '/node');
    // Install test module with hook implementation and make sure that
    // destination has changed to front page.
    \Drupal::service('module_installer')->install(['rabbit_hole_test_hooks']);
    $this->assertPageRedirect('/node', '/');
  }

  /**
   * Test URL redirects (destination and redirect code).
   */
  protected function assertPageRedirect($destination_path, $expected_path, $redirect_code = 301) {
    $settings = [
      'redirect' => $destination_path,
      'redirect_code' => $redirect_code,
    ];
    $entity = $this->createTestNode('page_redirect', $settings);

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($expected_path);
  }

  /**
   * Creates test node with provided action.
   *
   * @return \Drupal\node\NodeInterface
   *   Test node object.
   */
  protected function createTestNode($action = NULL, $settings = []) {
    $values = [
      'type' => 'article',
    ];
    if (isset($action)) {
      $values['rabbit_hole__settings'] = [
        'action' => $action,
        'settings' => $settings,
      ];
    }
    return $this->drupalCreateNode($values);
  }

  /**
   * Creates test file.
   *
   * @return \Drupal\file\FileInterface
   *   Test file object.
   */
  protected function createTestFile($filename) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uid' => 1,
      'filename' => "{$filename}.txt",
      'uri' => "public://{$filename}.txt",
      'filemime' => 'text/plain',
      'status' => 1,
    ]);
    file_put_contents($file->getFileUri(), $filename);
    $file->save();

    return $file;
  }

}
