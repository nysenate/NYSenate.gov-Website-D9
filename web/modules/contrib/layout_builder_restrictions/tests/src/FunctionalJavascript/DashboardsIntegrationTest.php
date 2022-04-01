<?php

namespace Drupal\Tests\layout_builder_restrictions\FunctionalJavascript;

/**
 * Demonstrate that Layout Builder Restrictions works with Dashboards.
 *
 * @group layout_builder_restrictions
 *
 * @requires dashboards
 */
class DashboardsIntegrationTest extends LayoutBuilderRestrictionsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_builder_restrictions',
    'dashboards',
    'field_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->strictConfigSchema = NULL;
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'configure any layout',
      'administer dashboards',
    ]));
  }

  /**
   * Verify that Layout Builder Restrictions does not break Dashboards.
   */
  public function testDashboardsWithRestrictionsEnabled() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/dashboards/add');
    $page->fillField('Administrative Label', 'Charlie');
    $this->assertNotEmpty($assert_session->waitForText('Machine name: charlie'));
    $page->pressButton('Save');
    $this->drupalGet('dashboards/charlie/layout');
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
