<?php

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

/**
 * Demonstrate that Layout Builder Restrictions works with Mini Layouts.
 *
 * @group layout_builder_restrictions
 *
 * @requires mini_layouts
 */
class MiniLayoutsIntegrationTest extends LayoutBuilderRestrictionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_builder_restrictions',
    'mini_layouts',
    'field_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'configure any layout',
      'administer mini layouts',
    ]));
  }

  /**
   * Verify that Layout Builder Restrictions does not break Mini Layouts.
   */
  public function testMiniLayoutsWithRestrictionsEnabled() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/mini_layouts/add');
    $page->fillField('Administrative Label', 'Charlie');
    $this->assertNotEmpty($assert_session->waitForText('Machine name: charlie'));
    $page->fillField('Label', 'Bravo');
    $this->assertNotEmpty($assert_session->waitForText('Machine name: bravo'));
    $page->pressButton('Save');
    $this->drupalGet('admin/structure/mini_layouts/manage/charlie/layout');
    $page->clickLink('Add section');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.layout-selection'));
    $page->clickLink('One column');
    $this->assertNotEmpty($assert_session->waitForText('Configure section'));
    $page->pressButton('Add section');
    $this->assertNotEmpty($assert_session->waitForText('You have unsaved changes'));
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForText('Choose a block'));
  }

}
