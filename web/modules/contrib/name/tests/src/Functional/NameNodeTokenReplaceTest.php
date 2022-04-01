<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests text replacements in content to check node name token replacement.
 *
 * @group name
 */
class NameNodeTokenReplaceTest extends NameTestBase {

  use NameTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'filter', 'token'];

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatterInterface
   */
  protected $formatter;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();

    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);
    $this->createNameField('field_name', 'node', 'article');
    $this->createNameField('field_realname', 'user', 'user');
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testNodeTokenReplacement() {
    $this->formatter = \Drupal::service('name.formatter');

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', 'field_realname')
      ->save();

    // Create a user and a node with populated name fields.
    $account = $this->createUser();
    $account->set('field_realname', [
      'title' => 'UUtt',
      'given' => 'UUgg',
      'middle' => 'UUmm UUnn',
      'family' => 'UUff',
      'generational' => 'Jr.',
      'credentials' => 'UUCreds, UUMoreCreds',
    ])->save();

    /* @var $node \Drupal\node\NodeInterface */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [
        [
          'value' => 'Regular NODE body for the test.',
          'summary' => 'Fancy NODE summary.',
          'format' => 'plain_text',
        ],
      ],
      'field_name' => [
        [
          'title' => 'Ttt',
          'given' => 'Ggg',
          'middle' => 'Mmm Nnnn',
          'family' => 'Fff',
          'generational' => 'Sr.',
          'credentials' => 'Creds, MoreCreds',
        ],
      ],
    ]);
    $node->save();

    /* @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $node->get('field_name')->get(0);
    $components = $item->filteredArray();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:field_name]'] = $this->formatter->format($components);
    $tests['[node:field_name:title]'] = $components['title'];
    $tests['[node:field_name:given]'] = $components['given'];
    $tests['[node:field_name:middle]'] = $components['middle'];
    $tests['[node:field_name:family]'] = $components['family'];
    $tests['[node:field_name:generational]'] = $components['generational'];
    $tests['[node:field_name:credentials]'] = $components['credentials'];

    // @todo: consider multiple value tests, "[node:field_name:1:family]".

    /* @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $account->get('field_realname')->get(0);
    $components = $item->filteredArray();

    $tests['[node:author:name]'] = $account->getAccountName();
    $tests['[node:author:account-name]'] = $account->getAccountName();
    $tests['[node:author:display-name]'] = $account->getDisplayName();
    $tests['[node:author:field_realname]'] = $this->formatter->format($components);
    $tests['[node:author:field_realname:family]'] = $components['family'];

    // @todo: consider current user tests, "[current-user:display-name]".

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEquals($output, (string) $expected, new FormattableMarkup('Node token %token replaced with %expected, got %actual.', [
        '%token' => $input,
        '%expected' => $expected,
        '%actual' => $output,
      ]));
      // @todo: caching tests.
      // @see NodeTokenReplaceTest.
      // $this->assertEquals($bubbleable_metadata, $metadata_tests[$input]);
    }
  }

}
