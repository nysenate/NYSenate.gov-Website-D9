<?php

namespace Drupal\Tests\webform_group\Functional;

/**
 * Tests webform group element access.
 *
 * @group webform_group
 */
class WebformGroupElementAccessTest extends WebformGroupBrowserTestBase {

  /**
   * Tests webform group element access.
   */
  public function testGroupElementAccess() {
    $assert_session = $this->assertSession();

    // Default group.
    $group = $this->createGroup(['type' => 'default']);

    // Webform node.
    $node = $this->createWebformNode('test_group_element_access');

    // Users.
    $outsider_user = $this->createUser();

    $member_user = $this->createUser();
    $group->addMember($member_user);

    $custom_user = $this->createUser();
    $group->addMember($custom_user, ['group_roles' => ['default-custom']]);

    $group->save();

    /* ********************************************************************** */
    // Webform node not related to any group.
    /* ********************************************************************** */

    // Logout.
    $this->drupalLogout();

    // Check that only the anonymous element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldExists('anonymous');
    $assert_session->fieldNotExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldNotExists('member');
    $assert_session->fieldNotExists('custom');

    // Login as an outsider user.
    $this->drupalLogin($outsider_user);

    // Check that only the authenticated element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('anonymous');
    $assert_session->fieldExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldNotExists('member');
    $assert_session->fieldNotExists('custom');

    // Login as a member user.
    $this->drupalLogin($member_user);

    // Check that only the authenticated element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('anonymous');
    $assert_session->fieldExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldNotExists('member');
    $assert_session->fieldNotExists('custom');

    /* ********************************************************************** */
    // Webform node related to a group.
    /* ********************************************************************** */

    // Add webform node to group.
    $group->addContent($node, 'group_node:webform');
    $group->save();

    // Logout.
    $this->drupalLogout();

    // Check that only the anonymous element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldExists('anonymous');
    $assert_session->fieldNotExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldNotExists('member');
    $assert_session->fieldNotExists('custom');

    // Login as an outsider user.
    $this->drupalLogin($outsider_user);

    // Check that only the authenticated and outsider element are displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('anonymous');
    $assert_session->fieldExists('authenticated');
    $assert_session->fieldExists('outsider');
    $assert_session->fieldNotExists('member');
    $assert_session->fieldNotExists('custom');

    // Login as a member user.
    $this->drupalLogin($member_user);

    // Check that only the authenticated element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('anonymous');
    $assert_session->fieldExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldExists('member');
    $assert_session->fieldNotExists('custom');

    // Login as a custom user.
    $this->drupalLogin($custom_user);

    // Check that only the authenticated element is displayed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('anonymous');
    $assert_session->fieldExists('authenticated');
    $assert_session->fieldNotExists('outsider');
    $assert_session->fieldExists('member');
    $assert_session->fieldExists('custom');
  }

}
