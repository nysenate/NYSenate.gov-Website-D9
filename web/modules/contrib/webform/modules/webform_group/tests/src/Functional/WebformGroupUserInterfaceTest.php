<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests webform group user interface integration.
 *
 * @group webform_group
 */
class WebformGroupUserInterfaceTest extends WebformGroupBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_ui'];

  /**
   * Tests webform group user interface.
   */
  public function testGroupUserInterfaceAccess() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Webform.
    /* ********************************************************************** */

    // Check 'Webform Access' integration.
    // @see webform_group_form_webform_settings_access_form_alter()
    $this->drupalGet('/admin/structure/webform/manage/contact/access');
    $assert_session->responseContains('<label for="edit-access-create-group-roles">Group (node) roles</label>');
    $assert_session->fieldExists('access[create][group_roles][]');

    // Add create access to webform for the member group role.
    $this->drupalGet('/admin/structure/webform/manage/contact/access');
    $edit = ['access[create][group_roles][]' => ['member']];
    $this->submitForm($edit, 'Save');

    // Check create access to webform for the member group role.
    \Drupal::entityTypeManager()->getStorage('webform')->resetCache();
    $webform = Webform::load('contact');
    $access_rules = $webform->getAccessRules();
    $this->debug($access_rules);
    $this->assertEquals($access_rules['create']['group_roles'], ['member']);

    /* ********************************************************************** */
    // Element.
    /* ********************************************************************** */

    // Check 'Element' integration.
    // @see webform_group_form_webform_ui_element_form_alter()
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $assert_session->fieldExists('properties[access_create_group_roles][]');

    // Add create access to name element for the member group role.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $edit = ['properties[access_create_group_roles][]' => 'member'];
    $this->submitForm($edit, 'Save');

    // Check create access to name element for the member group role.
    \Drupal::entityTypeManager()->getStorage('webform')->resetCache();
    $webform = Webform::load('contact');
    $element = $webform->getElement('name');
    $this->assertEquals($element['#access_create_group_roles'], ['member']);

    /* ********************************************************************** */
    // Handler.
    /* ********************************************************************** */

    // Check that group roles must be enabled for 'Email Handler' integration.
    // @see webform_group_form_webform_handler_form_alter()
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/add/email');
    $this->assertNoCssSelect('select[name="settings[to_mail][select]"] > optgroup[label="Group roles"]');
    $this->assertNoCssSelect('select[name="settings[to_mail][select]"] > optgroup > option[value="[webform_group:role:member]"]');
    $this->assertNoCssSelect('select[name="settings[to_mail][select]"] > optgroup > option[value="[webform_group:owner:mail]"]');

    // Enable group roles and owner.
    \Drupal::configFactory()->getEditable('webform_group.settings')
      ->set('mail.group_roles', ['member'])
      ->set('mail.group_owner', TRUE)
      ->save();

    // Check that enabled group roles are displayed.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/add/email');
    $this->assertCssSelect('select[name="settings[to_mail][select]"] > optgroup[label="Group roles"]');
    $this->assertCssSelect('select[name="settings[to_mail][select]"] > optgroup > option[value="[webform_group:role:member]"]');
  }

}
