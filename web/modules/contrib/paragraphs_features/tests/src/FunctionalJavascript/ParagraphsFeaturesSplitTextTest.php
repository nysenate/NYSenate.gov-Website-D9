<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

/**
 * Tests the paragraph text split feature.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesSplitTextTest extends ParagraphsFeaturesJavascriptTestBase {

  use CKEditor5TestTrait;

  /**
   * Trigger a keyup event on the selected element.
   *
   * Copied from CKEditor5TestBase class.
   *
   * @param string $selector
   *   The css selector for the element.
   * @param string $key
   *   The keyCode.
   */
  protected function triggerKeyUp(string $selector, string $key): void {

    $script = <<<JS
(function (selector, key) {
  const btn = document.querySelector(selector);
    btn.dispatchEvent(new KeyboardEvent('keydown', { key }));
    btn.dispatchEvent(new KeyboardEvent('keyup', { key }));
})('{$selector}', '{$key}')

JS;

    $options = [
      'script' => $script,
      'args' => [],
    ];

    $this->getSession()->getDriver()->getWebDriverSession()->execute($options);
  }

  /**
   * Create new text paragraph to end of paragraphs list.
   *
   * @param int $index
   *   Index of new paragraph.
   * @param string $text
   *   Text that will be filled to text field with CKEditor.
   *
   * @return string
   *   Returns CKEditor ID.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function createNewTextParagraph($index, $text): string {
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $this->scrollClick('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $ck_editor_id = $this->getCkEditorId($index);

    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id').setData('$text');");
    return $ck_editor_id;
  }

  /**
   * Click on split text button for paragraphs text field.
   *
   * @param int $ck_editor_id
   *   Id of CKEditor field in paragraphs.
   */
  protected function clickParagraphSplitButton($ck_editor_id): void {
    $button = $this->assertSession()
      ->waitForElementVisible('xpath', '//textarea[@data-ckeditor5-id="' . $ck_editor_id . '"]/following-sibling::div//button[span[text()="Split Paragraph"]]');
    $this->assertNotEmpty($button);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Set selection to beginning of an element containing a given string.
   *
   * @param int $ck_editor_id
   *   Id of CKEditor field in paragraphs.
   * @param string $needle
   *   String contained by element.
   */
  protected function setEditorSelection($ck_editor_id, $needle): void {
    $script = <<<JS
(function (editorId, needle) {
  const editor = Drupal.CKEditor5Instances.get(editorId);
  editor.model.change( writer => {
    const selection = writer.createSelection(editor.model.document.getRoot(), 'in');
    let newPosition;
    for (const range of selection.getRanges()) {
      for (const item of range.getItems()) {
        if (item.data?.includes(needle)) {
          newPosition = writer.createPositionAt(item, 'before');
          break;
        }
      }
    }
    const newRange = writer.createRange( newPosition );
    writer.setSelection( newRange );
    editor.focus()
  });
})('{$ck_editor_id}', '{$needle}')
JS;

    $this->getSession()->getDriver()->executeScript($script);
  }

  /**
   * Test split text feature.
   */
  public function testSplitTextFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of split text feature.
    $content_type = 'test_split_text';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->createEditor();

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that split text option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    $this->triggerKeyUp('.ckeditor5-toolbar-item-splitParagraph', 'ArrowDown');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');

    // Case 1 - simple text split.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    // Case 2 - simple split inside word.
    $paragraph_content = '<p>Content will be split inside the word spl-it.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content);

    // Make split of created text paragraph.
    $splitinsidescript = <<<JS
  (function (editorId) {
    const editor = Drupal.CKEditor5Instances.get(editorId);
    editor.model.change( writer => {
      const range = editor.model.createRangeIn( editor.model.document.getRoot() );
      const walker = range.getWalker();
      walker.next();
      const value = walker.next().value;
      value.item.offsetInText = 19;
      const position = writer.createPositionAt(value.item, 'before');
      writer.setSelection(position);
      editor.focus();
    });

  })('{$ck_editor_id}')
JS;
    $driver->executeScript($splitinsidescript);
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      '<p>Content will be spl</p>',
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
    static::assertEquals(
      '<p>it inside the word spl-it.</p>',
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    // Case 3 - split text works after removal of paragraph.
    $this->drupalGet("node/add/$content_type");
    $this->createNewTextParagraph(0, '');

    // Remove the paragraph.
    $driver->executeScript("jQuery('[name=\"field_paragraphs_0_remove\"]').trigger('mousedown');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create new text paragraph.
    $ck_editor_id = $this->createNewTextParagraph(1, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(1);
    $ck_editor_id_1 = $this->getCkEditorId(2);
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    // Case 4 - add of new paragraph after text split.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Set new data to both split paragraphs.
    $paragraph_content_0_new = '<p>Content that will be placed into the first paragraph after split.</p>';
    $paragraph_content_1_new = '<p>Content that will be placed into the second paragraph after split.</p>';
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').setData('$paragraph_content_0_new');");
    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').setData('$paragraph_content_1_new');");

    // Add new text paragraph.
    $this->createNewTextParagraph(2, '');

    // Check if all texts are in the correct paragraph.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_id_2 = $this->getCkEditorId(2);
    $ck_editor_content_0 = $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();");
    $ck_editor_content_1 = $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();");
    $ck_editor_content_2 = $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_2').getData();");

    static::assertEquals($paragraph_content_0_new, $ck_editor_content_0);
    static::assertEquals($paragraph_content_1_new, $ck_editor_content_1);
    static::assertEquals('', $ck_editor_content_2);

    // Case 5 - test split in middle of formatted text.
    $text = '<p>Text start</p><ol><li>line 1</li><li>line 2 with some <strong>bold text</strong> and back to normal</li><li>line 3</li></ol><p>Text end after indexed list</p>';
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $text);

    // Set selection between "bold" and "text".
    $script = <<<JS
(function (editorId) {
  const editor = Drupal.CKEditor5Instances.get(editorId);
  editor.model.change( writer => {
    let newPosition;
    const selection = writer.createSelection(editor.model.document.getRoot(), 'in');
    for (const item of selection.getFirstRange().getItems({ direction: 'backward' })) {
      if (item.getAttribute('bold')) {
        item.offsetInText = 4;
        newPosition = writer.createPositionAt(item, 'before');
        break;
      }
    }
    writer.setSelection( newPosition );
    editor.focus()
  })
})('{$ck_editor_id}')
JS;
    $driver->executeScript($script);
    $this->clickParagraphSplitButton($ck_editor_id);

    // Check if all texts are correct.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_content_0 = $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();");
    $ck_editor_content_1 = $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();");

    $expected_content_0 = '<p>Text start</p><ol><li>line 1</li><li>line 2 with some <strong>bold</strong></li></ol>';
    $expected_content_1 = '<ol><li><strong>text</strong> and back to normal</li><li>line 3</li></ol><p>Text end after indexed list</p>';

    static::assertEquals($expected_content_0, $ck_editor_content_0);
    static::assertEquals($expected_content_1, $ck_editor_content_1);

    // Case 6 - split paragraph with multiple text fields.
    $this->addParagraphsType("test_3_text_fields");
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_1', 'text_long');
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_2', 'text_long');
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_3', 'text_long');

    $this->drupalGet("node/add/$content_type");

    $page->find('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3_text_fields")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add required texts to text fields.
    $paragraph_content_0_text_0 = '<p>Content that will be in the first text field.</p>';
    $paragraph_content_0_text_1 = $paragraph_content_0 . $paragraph_content_1;
    $paragraph_content_0_text_2 = '<p>Content that will be in the last text field.</p>';
    $ck_editor_id_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[1]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[2]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[3]')->getAttribute('data-ckeditor5-id');

    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').setData('$paragraph_content_0_text_0');");
    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').setData('$paragraph_content_0_text_1');");
    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id_2').setData('$paragraph_content_0_text_2');");

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id_1, 'second');
    $this->clickParagraphSplitButton($ck_editor_id_1);

    // Validate split results in all 6 CKEditors in 2 paragraphs.
    $ck_editor_id_para_0_text_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[1]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_para_0_text_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[2]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_para_0_text_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[3]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_para_1_text_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[1]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_para_1_text_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[2]')->getAttribute('data-ckeditor5-id');
    $ck_editor_id_para_1_text_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[3]')->getAttribute('data-ckeditor5-id');
    static::assertEquals(
      $paragraph_content_0_text_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_0_text_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_0_text_1').getData();")
    );
    static::assertEquals(
      $paragraph_content_0_text_2,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_0_text_2').getData();")
    );
    static::assertEquals(
      '',
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_1_text_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_1_text_1').getData();")
    );
    static::assertEquals(
      '',
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_para_1_text_2').getData();")
    );

    // Case 7 - simple text split with auto-collapse.
    // 7.1 - Enable auto-collapse.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set edit mode to closed.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][edit_mode]', 'closed');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][edit_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set auto-collapse mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][autocollapse]', 'all');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][autocollapse]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // 7.2 - Test that simple text split works with auto-collapse.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results. First newly created paragraph.
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    // And then original collapsed paragraph.
    $this->scrollClick('css', '[name=field_paragraphs_0_edit]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $ck_editor_id_0 = $this->getCkEditorId(0);
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );

    // Case 8 - simple text split with add-in-between.
    // 8.1 - Enable add-in-between.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');
    $this->assertEquals(TRUE, $session->evaluateScript("document.querySelector('.paragraphs-features__add-in-between__option').checked"), 'Checkbox should be checked.');

    // Disable auto-collapse.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][autocollapse]', 'none');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][autocollapse]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set edit mode to open.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][edit_mode]', 'open');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][edit_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // 8.2 - Test that simple text split works with add-in-between.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $this->scrollClick('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button ")])[1]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $ck_editor_id = $this->getCkEditorId(0);

    $driver->executeScript("Drupal.CKEditor5Instances.get('$ck_editor_id').setData('$paragraph_content_0$paragraph_content_1');");

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results. First newly created paragraph.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
  }

  /**
   * Test splitting twice.
   */
  public function testSplitTwice() {

    // Create paragraph types and content types with required configuration for
    // testing of split text feature.
    $content_type = 'test_split_text';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->createEditor();

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that split text option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/config/content/formats/manage/filtered_html');

    $this->triggerKeyUp('.ckeditor5-toolbar-item-splitParagraph', 'ArrowDown');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save configuration');

    // Case 1 - simple text split.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';
    $paragraph_content_2 = '<p>Content that will be in the third paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1 . $paragraph_content_2);

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id, 'second');
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . $paragraph_content_2,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );

    // Make split of created text paragraph.
    $this->setEditorSelection($ck_editor_id_1, 'third');
    $this->clickParagraphSplitButton($ck_editor_id_1);

    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_id_2 = $this->getCkEditorId(2);
    static::assertEquals(
      $paragraph_content_0,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_0').getData();")
    );
    static::assertEquals(
      $paragraph_content_1,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_1').getData();")
    );
    static::assertEquals(
      $paragraph_content_2,
      $driver->evaluateScript("Drupal.CKEditor5Instances.get('$ck_editor_id_2').getData();")
    );
  }

}
