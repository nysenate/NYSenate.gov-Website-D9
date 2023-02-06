<?php

namespace Drupal\Tests\ajax_comments\Functional;

use Drupal\Tests\comment\Functional\CommentTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the \Drupal\ajax_comments\Form\SettingsForm.
 *
 * @group ajax_comments
 */
class AjaxCommentsSettingsFormTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ajax_comments',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_roles = $this->adminUser->getRoles();
    $admin_role = Role::load(reset($admin_roles));
    $this->grantPermissions($admin_role, [
      'administer site configuration',
      'administer node display',
    ]);
  }

  /**
   * Test the \Drupal\ajax_comments\Form\SettingsForm.
   */
  public function testAjaxCommentsSettings() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/ajax_comments');
    // Check that the page loads.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t("Enable Ajax Comments on the comment fields' display settings"));
    $this->clickLink('Content: Article');
    $this->assertSession()->addressEquals('/admin/structure/types/manage/article/display');
    $this->assertSession()->statusCodeEquals(200);

    // Open comment settings.
    $this->submitForm([], 'comment_settings_edit');
    // Disable ajax comments.
    $this->submitForm(['fields[comment][settings_edit_form][third_party_settings][ajax_comments][enable_ajax_comments]' => '0'], 'comment_plugin_settings_update');
    // Save display mode.
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);
  }

}
