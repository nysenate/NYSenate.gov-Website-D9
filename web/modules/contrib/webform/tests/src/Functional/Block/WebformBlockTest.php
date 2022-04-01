<?php

namespace Drupal\Tests\webform\Functional\Block;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform block.
 *
 * @group webform
 */
class WebformBlockTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_confirmation_inline', 'test_confirmation_message'];

  /**
   * Tests webform block.
   */
  public function testBlock() {
    $assert_session = $this->assertSession();

    // Place block.
    $block = $this->drupalPlaceBlock('webform_block', [
      'webform_id' => 'contact',
    ]);

    // Check contact webform.
    $this->drupalGet('<front>');
    $assert_session->responseContains('webform-submission-contact-add-form');

    // Check contact webform with default data.
    $block->getPlugin()->setConfigurationValue('default_data', "name: 'John Smith'");
    $block->save();
    $this->drupalGet('<front>');
    $assert_session->responseContains('webform-submission-contact-add-form');
    $assert_session->fieldValueEquals('edit-name--2', 'John Smith');

    // Check confirmation inline webform.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_inline');
    $block->save();
    $this->drupalGet('<front>');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('This is a custom inline confirmation message.');

    // Check confirmation message webform displayed on front page.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_message');
    $block->save();
    $this->drupalGet('<front>');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('This is a <b>custom</b> confirmation message.');
    $assert_session->addressEquals('/user/login');

    // Check confirmation message webform display on webform URL.
    $block->getPlugin()->setConfigurationValue('redirect', TRUE);
    $block->save();
    $this->drupalGet('<front>');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('This is a <b>custom</b> confirmation message.');
    $assert_session->addressEquals('webform/test_confirmation_message');

  }

}
