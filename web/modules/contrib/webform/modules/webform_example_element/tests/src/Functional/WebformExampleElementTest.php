<?php

namespace Drupal\Tests\webform_example_element\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform example element.
 *
 * @group webform_example_element
 */
class WebformExampleElementTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_example_element'];

  /**
   * Tests webform example element.
   */
  public function testWebformExampleElement() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('webform_example_element');

    // Check form element rendering.
    $this->drupalGet('/webform/webform_example_element');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $assert_session->responseContains('<div class="js-form-item form-item js-form-type-webform-example-element form-item-webform-example-element js-form-item-webform-example-element">');
    $assert_session->responseContains('<label for="edit-webform-example-element">Webform Example Element</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-example-element" type="text" id="edit-webform-example-element" name="webform_example_element" value="" size="60" class="form-text webform-example-element" />');

    // Check webform element submission.
    $edit = [
      'webform_example_element' => '{Test}',
      'webform_example_element_multiple[items][0][_item_]' => '{Test 01}',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getElementData('webform_example_element'), '{Test}');
    $this->assertEquals($webform_submission->getElementData('webform_example_element_multiple'), ['{Test 01}']);
  }

}
