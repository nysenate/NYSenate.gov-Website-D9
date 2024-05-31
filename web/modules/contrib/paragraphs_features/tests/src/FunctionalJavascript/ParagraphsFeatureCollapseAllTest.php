<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Test the show_collapse_all setting.
 *
 * @group paragraphs_features
 */
class ParagraphsFeatureCollapseAllTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Test display of collapse all.
   */
  public function testCollapseAllOption() {
    // Create content type with paragrapjs field.
    $content_type = 'test_collapse_all';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->setupParagraphSettings($content_type);

    // Check that Edit all and Collapse all buttons are present.
    $this->drupalGet("node/add/$content_type");
    $this->scrollClick('xpath', '//input[@data-drupal-selector="field-paragraphs-test-nested-add-more"]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('xpath', '//input[starts-with(@data-drupal-selector,"field-paragraphs-edit-all")]');
    $this->assertSession()->elementExists('xpath', '//input[starts-with(@data-drupal-selector,"field-paragraphs-collapse-all")]');

    // Enable hide Collapse all option.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->uncheckField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][show_collapse_all]');
    $this->submitForm([], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Check that Edit all button is and Collapse all button is not present.
    $this->drupalGet("node/add/$content_type");
    $this->scrollClick('xpath', '//input[@data-drupal-selector="field-paragraphs-test-nested-add-more"]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('xpath', '//input[starts-with(@data-drupal-selector,"field-paragraphs-edit-all")]');
    $this->assertSession()->elementNotExists('xpath', '//input[starts-with(@data-drupal-selector,"field-paragraphs-collapse-all")]');
  }

  /**
   * Setup paragraphs field for a content type.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   */
  protected function setupParagraphSettings($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();

    // Have a default paragraph, it simplifies the clicking on the edit page.
    $this->config('core.entity_form_display.node.' . $content_type . '.default')
      ->set('content.field_paragraphs.settings.default_paragraph_type', 'test_1')
      ->set('content.field_paragraphs.settings.add_mode', 'button')
      ->set('content.field_paragraphs.third_party_settings.paragraphs_features.show_drag_and_drop', FALSE)
      ->set('content.field_paragraphs.settings.features.duplicate', '0')
      ->set('content.field_paragraphs.settings.features.collapse_edit_all', 'collapse_edit_all')
      ->save();

    $this->drupalGet($currentUrl);
  }

}
