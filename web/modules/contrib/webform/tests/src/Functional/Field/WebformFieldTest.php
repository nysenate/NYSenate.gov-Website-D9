<?php

namespace Drupal\Tests\webform\Functional\Field;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests the webform (entity reference) field.
 *
 * @group webform
 */
class WebformFieldTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui'];

  /**
   * Tests the webform (entity reference) field.
   */
  public function testWebformField() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $this->drupalCreateContentType(['type' => 'page']);

    FieldStorageConfig::create([
      'field_name' => 'field_webform',
      'type' => 'webform',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_webform',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'webform',
    ])->save();
    $form_display = $display_repository->getFormDisplay('node', 'page');
    $form_display->setComponent('field_webform', [
      'type' => 'webform_entity_reference_select',
      'settings' => [],
    ]);
    $form_display->save();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check that webform select menu is visible.
    $this->drupalGet('/node/add/page');
    $this->assertNoCssSelect('#edit-field-webform-0-target-id optgroup');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');

    // Check if the webform settings wrapper is there.
    $this->assertCssSelect('details#edit-field-webform-0-settings');

    // Check that the webform status settings fieldset is there.
    $this->assertCssSelect('fieldset#edit-field-webform-0-settings-status--wrapper');
    $this->assertCssSelect('input#edit-field-webform-0-settings-status-open');
    $this->assertCssSelect('input#edit-field-webform-0-settings-status-closed');
    $this->assertCssSelect('input#edit-field-webform-0-settings-status-scheduled');

    // Check that the webform schedule fields are there.
    $this->assertCssSelect('input#edit-field-webform-0-settings-scheduled-open-date');
    $this->assertCssSelect('input#edit-field-webform-0-settings-scheduled-open-time');
    $this->assertCssSelect('input#edit-field-webform-0-settings-scheduled-close-date');
    $this->assertCssSelect('input#edit-field-webform-0-settings-scheduled-close-time');

    // Check that the default submission data (YAML) field is there.
    $this->assertCssSelect('textarea#edit-field-webform-0-settings-default-data');

    // Disable showing the status options fields.
    $form_display->setComponent('field_webform', [
      'type' => 'webform_entity_reference_select',
      'settings' => [
        'allow_status' => FALSE,
      ],
    ]);
    $form_display->save();

    // Check if the status options fields are removed.
    $this->drupalGet('/node/add/page');
    $this->assertNoCssSelect('fieldset#edit-field-webform-0-settings-status--wrapper');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-status-open');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-status-closed');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-status-scheduled');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-scheduled-open-date');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-scheduled-open-time');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-scheduled-close-date');
    $this->assertNoCssSelect('input#edit-field-webform-0-settings-scheduled-close-time');

    // Disable showing the default submission data (YAML) field.
    $form_display->setComponent('field_webform', [
      'type' => 'webform_entity_reference_select',
      'settings' => [
        'default_data' => FALSE,
      ],
    ]);
    $form_display->save();

    // Check if the default submission data (YAML) field is removed.
    $this->drupalGet('/node/add/page');
    $this->assertNoCssSelect('textarea#edit-field-webform-0-settings-default-data');

    // Disable showing both the status options & default submission data (YAML) field.
    $form_display->setComponent('field_webform', [
      'type' => 'webform_entity_reference_select',
      'settings' => [
        'allow_status' => FALSE,
        'default_data' => FALSE,
      ],
    ]);
    $form_display->save();

    // Check if the webform settings wrapper is removed.
    $this->drupalGet('/node/add/page');
    $this->assertNoCssSelect('details#edit-field-webform-0-settings');

    // Add category to 'contact' webform.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    $webform->set('categories', ['{Some category}']);
    $webform->save();

    // Check that webform select menu included optgroup.
    $this->drupalGet('/node/add/page');
    $this->assertCssSelect('#edit-field-webform-0-target-id optgroup[label="{Some category}"]');

    // Create a second webform.
    $webform_2 = $this->createWebform();

    // Check that webform 2 is included in the select menu.
    $this->drupalGet('/node/add/page');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');
    $assert_session->optionExists('edit-field-webform-0-target-id', $webform_2->id());

    // Limit the webform select menu to only the contact form.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_webform_settings_edit');
    $this->submitForm(['fields[field_webform][settings_edit_form][settings][webforms][]' => ['contact']], 'Save');

    // Check that webform 2 is NOT included in the select menu.
    $this->drupalGet('/node/add/page');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');
    $assert_session->optionNotExists('edit-field-webform-0-target-id', $webform_2->id());
  }

}
