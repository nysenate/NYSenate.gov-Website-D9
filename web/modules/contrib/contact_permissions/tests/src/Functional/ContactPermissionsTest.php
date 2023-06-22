<?php

namespace Drupal\Tests\contact_permissions\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the permission provided by contact_permissions module.
 *
 * @package Drupal\Tests\contact_permissions\Functional
 *
 * @group contact_permissions
 */
class ContactPermissionsTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contact_permissions_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the contactable role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $contactable;

  /**
   * A user with the noncontactable role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonContactable;

  /**
   * A user with the administrator role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $contactable = $this->drupalCreateUser();
    $contactable->addRole('contactable');
    $contactable->save();
    $this->contactable = $contactable;

    $noncontactable = $this->drupalCreateUser();
    $noncontactable->addRole('noncontactable');
    $noncontactable->save();
    $this->nonContactable = $noncontactable;

    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * Tests contact permissions.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testContactPermissions() {
    // Assert that the roles defined by view_profiles_perms_test module get
    // their permissions generated and appear correctly in the UI.
    $assert = $this->assertSession();
    $this->drupalLogin($this->admin);
    $this->drupalGet('admin/people/permissions');
    $assert->pageTextContains('Have a personal contact form');
    $roles = user_roles(TRUE);
    foreach ($roles as $role) {
      $label = $role->label();
      $assert->pageTextContains("Use $label's personal contact forms");
    }
    $assert->pageTextNotContains("Use Anonymous user's personal contact forms");
    $assert->checkboxChecked('contactable[have a personal contact form]');
    $assert->checkboxNotChecked('contactable[use noncontactable personal contact forms]');
    $assert->checkboxChecked('noncontactable[use contactable personal contact forms]');
    $assert->checkboxNotChecked('anonymous[have a personal contact form]');
    $assert->checkboxNotChecked('authenticated[have a personal contact form]');

    // Assert contactable can configure their contact form.
    $this->drupalGet('user/' . $this->contactable->id() . '/edit');
    $assert->pageTextContains('Contact settings');
    $assert->pageTextContains('Personal contact form');
    $assert->checkboxChecked('contact');

    // Assert users without the specific per role permission, but with the
    // global 'access user contact forms' permission can still use contactable
    // contact form.
    $user = $this->drupalCreateUser(['access user contact forms']);
    $this->drupalLogin($user);
    $this->drupalGet('user/' . $this->contactable->id() . '/contact');
    $assert->statusCodeEquals(200);
    // Contact forms for users with the noncontactable role should still be
    // unavailable.
    $this->drupalGet('user/' . $this->nonContactable->id() . '/contact');
    $assert->statusCodeEquals(403);

    // Assert users with the specific per role permission can access
    // contactable contact form.
    $this->drupalLogin($this->nonContactable);
    $this->drupalGet('user/' . $this->contactable->id() . '/contact');
    $assert->statusCodeEquals(200);

    // Assert users without the permission cannot configure their contact form.
    $this->drupalGet('user/' . $this->nonContactable->id() . '/edit');
    $assert->pageTextNotContains('Contact settings');
    $assert->pageTextNotContains('Personal contact form');
    // Ensure the user edit form can be saved fine.
    $this->submitForm([], 'Save', 'user-form');
    $assert->pageTextContains('The changes have been saved.');

    // Assert users with more than one role, and only one with access.
    $user = $this->drupalCreateUser();
    $user->addRole('contactable');
    $user->addRole('noncontactable');
    $user->save();
    $this->drupalGet('user/' . $user->id() . '/contact');
    $assert->statusCodeEquals(200);

    // Assert users can't visit their own contact page.
    $this->drupalGet('user/' . $this->nonContactable->id() . '/contact');
    $assert->statusCodeEquals(403);

    // An inactive/blocked user's contact page should never be affected by our
    // permissions.
    $this->contactable->block();
    $this->contactable->save();
    $this->drupalGet('user/' . $this->contactable->id() . '/contact');
    $assert->statusCodeEquals(403);

    // Assert users can still deactivate their personal contact forms.
    $this->contactable->activate();
    $this->contactable->save();
    $this->drupalLogin($this->contactable);
    $edit = [
      'contact' => FALSE,
    ];
    $this->drupalPostForm('user/' . $this->contactable->id() . '/edit', $edit, 'Save');
    $assert->pageTextContains('The changes have been saved.');
    // Assert users can no longer access contactable contact form.
    $this->drupalLogin($this->nonContactable);
    $this->drupalGet('user/' . $this->contactable->id() . '/contact');
    $assert->statusCodeEquals(403);
  }

}
