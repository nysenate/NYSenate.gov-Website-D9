<?php

namespace Drupal\Tests\views_bulk_edit\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\NodeInterface;

/**
 * @coversDefaultClass \Drupal\views_bulk_edit\Plugin\Action\ModifyEntityValues
 * @group views_bulk_edit
 */
class ViewsBulkEditModifyEntityValuesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'views_bulk_operations',
    'views_bulk_operations_test',
    'views_bulk_edit',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some nodes for testing.
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->createContentType(['type' => 'page', 'name' => 'Page']);

    // Create a text field on the page content type.
    $entityTypeManager = $this->container->get('entity_type.manager');
    $entityTypeManager->getStorage('field_storage_config')->create([
      'field_name' => 'text',
      'entity_type' => 'node',
      'type' => 'text',
      'module' => 'text',
      'cardinality' => 1,
    ])->save();
    $entityTypeManager->getStorage('field_config')->create([
      'field_name' => 'text',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Field text',
    ])->save();
    $entityTypeManager->getStorage('entity_form_display')->load('node.page.default')->setComponent('text', [
      'type' => 'text_textfield',
      'region' => 'content',
      'settings' => [
        'size' => 10,
      ],
    ])->save();

    $this->testNodes = [];
    $time = \Drupal::time()->getRequestTime();
    for ($i = 0; $i < 4; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $time -= $i;
      $type = ($i % 2 == 0) ? 'article' : 'page';
      $this->testNodes[] = $this->drupalCreateNode([
        // Bundles will be: page, article, page, article.
        'type' => $type,
        'title' => 'Title ' . $i . ' (' . $type . ')',
        'sticky' => FALSE,
        'created' => $time,
        'changed' => $time,
      ]);
    }

  }

  /**
   * Test the bulk edit operation.
   */
  public function testViewsBulkEdit() {

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'bypass node access',
      'execute advanced test action',
    ]);
    $this->drupalLogin($admin_user);

    // Modify config of the test view: add the views_bulk_edit operation,
    // set items per page to 10 and offset to 0.
    $testViewConfig = $this->container->get('config.factory')->getEditable('views.view.views_bulk_operations_test_advanced');
    $configData = $testViewConfig->getRawData();
    $action = count($configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions']);
    $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'][$action] = [
      'action_id' => 'views_bulk_edit',
      'preconfiguration' => [
        'label_override' => '',
      ],
    ];
    $configData['display']['default']['display_options']['pager']['options']['items_per_page'] = 10;
    $configData['display']['default']['display_options']['pager']['options']['offset'] = 0;
    $testViewConfig->setData($configData);
    $testViewConfig->save();

    // Post the VBO form with one page and one article selected.
    $edit = [
      'action' => $action,
      'views_bulk_operations_bulk_form[1]' => TRUE,
      'views_bulk_operations_bulk_form[2]' => TRUE,
    ];
    $this->drupalPostForm('views-bulk-operations-test-advanced', $edit, t('Apply to selected items'));

    // Post the configuration form: modify status and text value field on the
    // article content type.
    $expected_text_value = 'some text';
    $this->drupalPostForm(NULL, [
      'node[article][_field_selector][status]' => TRUE,
      'node[article][status][value]' => FALSE,
      'node[page][_field_selector][status]' => TRUE,
      'node[page][status][value]' => FALSE,
      'node[page][_field_selector][text]' => TRUE,
      'node[page][text][0][value]' => $expected_text_value,
    ], t('Apply'));

    // Assert if field values have been changed on the selected entities
    // and unchanged otherwise.
    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    foreach ($this->testNodes as $index => $node) {
      // Reload the node.
      $node = $nodeStorage->load($node->id());
      $status = intval($node->status->value);
      $text = isset($node->text) ? $node->text->value : FALSE;

      switch ($index) {
        case 0:
          $this->assertEquals(NodeInterface::PUBLISHED, $status);
          $this->assertEquals(FALSE, $text);
          break;

        case 1:
          $this->assertEquals(NodeInterface::NOT_PUBLISHED, $status);
          $this->assertEquals($expected_text_value, $text);
          break;

        case 2:
          $this->assertEquals(NodeInterface::NOT_PUBLISHED, $status);
          $this->assertEquals(FALSE, $text);
          break;

        case 3:
          $this->assertEquals(NodeInterface::PUBLISHED, $status);
          $this->assertEquals('', $text);
          break;
      }
    }
  }

}
