<?php

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Demonstrate that Layout Builder Restrictions works with Layout Library.
 *
 * @group layout_builder_restrictions
 *
 * @requires layout_library
 */
class LayoutLibraryIntegrationTest extends LayoutBuilderRestrictionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_builder_restrictions',
    'layout_library',
    'field_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'alpha', 'name' => 'Alpha']);

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'configure any layout',
      'administer node display',
    ]));
  }

  /**
   * Verify that Layout Builder Restrictions does not break Layout Library.
   */
  public function testLayoutLibraryWithRestrictionsEnabled() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet(Url::fromRoute('entity.layout.add_form'));
    $page->fillField('Label', 'Charlie');
    $this->assertNotEmpty($assert_session->waitForText('Machine name: charlie'));
    $page->selectFieldOption('Entity Type', 'node:alpha');
    $page->pressButton('Save');
    $page->clickLink('Add section');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.layout-selection'));
    $page->clickLink('One column');
    $this->assertNotEmpty($assert_session->waitForText('Configure section'));
  }

}
