<?php

namespace Drupal\Tests\views_field_view\Kernel;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use Drupal\views_field_view\Plugin\views\field\View as ViewField;

/**
 * Tests the views field view handler methods.
 *
 * @group views_field_view
 */
class ViewFieldUnitTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views_field_view',
    'views_field_view_test_config',
    'user',
  ];

  /**
   * Views to enable.
   *
   * @var array
   */
  public static $testViews = [
    'views_field_view_test_parent_normal',
    'views_field_view_test_child_normal',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(get_class($this), ['views_field_view_test_config']);
  }

  /**
   * Test normal view embedding.
   */
  public function testNormalView() {
    $parent_view = Views::getView('views_field_view_test_parent_normal');
    $parent_view->preview();

    // Check that the child view has the same title as the parent one.
    foreach ($parent_view->result as $index => $values) {
      $name = $parent_view->style_plugin->getField($index, 'name');
      $child_view_field = $parent_view->style_plugin->getField($index, 'view');
      $this->assertStringContainsString((string) $name, (string) $child_view_field);
    }

    // It's impossible to check the actual result of the child view, because the
    // object is not saved.
  }

  /**
   * Test field handler methods in a unit test like way.
   */
  public function testFieldHandlerMethods() {
    $view = Views::getView('views_field_view_test_parent_normal');
    $view->initDisplay();
    $view->initHandlers();
    $view->setArguments(['Hey jude']);
    $view->execute();
    $view->style_plugin->render();

    /** @var \Drupal\views_field_view\Plugin\views\field\View $field_handler */
    $field_handler = $view->field['view'];

    $this->assertTrue($field_handler instanceof ViewField);

    // Test the split_tokens() method.
    $result = $field_handler->splitTokens('{{ raw_fields.uid }},{{ fields.nid }}');
    $expected = ['{{ raw_fields.uid }}', '{{ fields.nid }}'];
    $this->assertEquals($result, $expected, 'The token string has been split correctly (",").');

    $result = $field_handler->splitTokens('{{ raw_fields.uid }}/{{ fields.nid }}');
    $this->assertEquals($result, $expected, 'The token string has been split correctly ("/").');

    // Test the get_token_argument() method.
    $result = $field_handler->getTokenValue('{{ raw_fields.id }}', $view->result[0], $view);
    $this->assertEquals(2, $result);

    $result = $field_handler->getTokenValue('{{ fields.id }}', $view->result[0], $view);
    $this->assertEquals(3, $result);

    $result = $field_handler->getTokenValue('{{ raw_fields.id_1 }}', $view->result[0], $view);
    $this->assertEquals(2, $result);

    $result = $field_handler->getTokenValue('{{ fields.id_1 }}', $view->result[0], $view);
    $this->assertEquals(3, $result);

    $result = $field_handler->getTokenValue('{{ raw_fields.name }}', $view->result[0], $view);
    $this->assertEquals('George', $result);

    $result = $field_handler->getTokenValue('{{ fields.name }}', $view->result[0], $view);
    $this->assertEquals('Ringo', $result);

    $result = $field_handler->getTokenValue('{{ raw_arguments.null }}', $view->result[0], $view);
    $this->assertEquals('Hey jude', $result);

    $result = $field_handler->getTokenValue('{{ arguments.null }}', $view->result[0], $view);
    $this->assertEquals('Hey jude', $result);
  }

}
