<?php

namespace Drupal\Tests\views_field_view\Functional;

use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the UI of views_field_view.
 *
 * @see \Drupal\views_field_view\Plugin\views\field\View
 *
 * @group views_field_view
 */
class ViewFieldUITest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
    parent::setUp($import_test_views = TRUE);

    ViewTestData::createTestViews(get_class($this), ['views_field_view_test_config']);
  }

  /**
   * Tests the UI of views_field_view.
   */
  public function testViewsFieldUi() {
    $this->drupalGet('admin/structure/views/view/views_field_view_test_parent_normal/edit/default');
    $this->clickLink('Global: View (View)');

    $result = $this->cssSelect('details#edit-options-available-tokens div.item-list li');
    $this->assertEqual(10, count($result));

    $this->assertEqual('{{ raw_fields.id }} == Views test: ID (raw)', $result[0]->getText());
    $this->assertEqual('{{ fields.id }} == Views test: ID (rendered)', $result[1]->getText());
    $this->assertEqual('{{ raw_fields.id_1 }} == Views test: ID (raw)', $result[2]->getText());
    $this->assertEqual('{{ fields.id_1 }} == Views test: ID (rendered)', $result[3]->getText());
    $this->assertEqual('{{ raw_fields.name }} == Views test: Name (raw)', $result[4]->getText());
    $this->assertEqual('{{ fields.name }} == Views test: Name (rendered)', $result[5]->getText());
    $this->assertEqual('{{ raw_fields.view }} == Global: View (raw)', $result[6]->getText());
    $this->assertEqual('{{ fields.view }} == Global: View (rendered)', $result[7]->getText());
    $this->assertEqual('{{ arguments.null }} == Global: Null title', $result[8]->getText());
    $this->assertEqual('{{ raw_arguments.null }} == Global: Null input', $result[9]->getText());
  }

}
