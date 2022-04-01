<?php

namespace Drupal\Tests\multiline_config\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\multiline_config\MultilineConfigFileStorage;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Test multiline config file storage.
 *
 * @coversDefaultClass \Drupal\multiline_config\MultilineConfigFileStorage
 *
 * @group multiline_config
 */
class MultilineConfigFileStorageTest extends UnitTestCase {

  /**
   * The config file storage.
   *
   * @var \Drupal\multiline_config\MultilineConfigFileStorage
   */
  protected $configStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    vfsStream::setup('multilineConfigDir');
    $directory = vfsStream::url('multilineConfigDir');
    $this->configStorage = new MultilineConfigFileStorage($directory);
  }

  /**
   * Tests the encode configuration data.
   *
   * @param string $original
   *   The original string.
   * @param string $expected
   *   The encoded string expected.
   *
   * @dataProvider providerConfigurations
   */
  public function testEncode($original, $expected) {
    $decoded_data = Yaml::decode($original);
    $encoded_data = $this->configStorage->encode($decoded_data);
    $this->assertEquals($expected, $encoded_data);
  }

  /**
   * Data provider for testCheckAccess().
   *
   * @see testCheckAccess()
   */
  public function providerConfigurations() {
    return [
      /* @see core/modules/user/config/install/user.mail.yml */
      ['cancel_confirm:
  body: "[user:display-name],\n\nA request to cancel your account has been made at [site:name].\n\nYou may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:\n\n[user:cancel-url]\n\nNOTE: The cancellation of your account is not reversible.\n\nThis link expires in one day and nothing will happen if it is not used.\n\n--  [site:name] team"
  subject: \'Account cancellation request for [user:display-name] at [site:name]\'
password_reset:
  body: "[user:display-name],\n\nA request to reset the password for your account has been made at [site:name].\n\nYou may now log in by clicking this link or copying and pasting it into your browser:\n\n[user:one-time-login-url]\n\nThis link can only be used once to log in and will lead you to a page where you can set your password. It expires after one day and nothing will happen if it\'s not used.\n\n--  [site:name] team"
  subject: \'Replacement login information for [user:display-name] at [site:name]\'
register_admin_created:
  body: "[user:display-name],\n\nA site administrator at [site:name] has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser:\n\n[user:one-time-login-url]\n\nThis link can only be used once to log in and will lead you to a page where you can set your password.\n\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\n\nusername: [user:name]\npassword: Your password\n\n--  [site:name] team"
  subject: \'An administrator created an account for you at [site:name]\'
register_no_approval_required:
  body: "[user:display-name],\n\nThank you for registering at [site:name]. You may now log in by clicking this link or copying and pasting it into your browser:\n\n[user:one-time-login-url]\n\nThis link can only be used once to log in and will lead you to a page where you can set your password.\n\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\n\nusername: [user:name]\npassword: Your password\n\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name]\'
register_pending_approval:
  body: "[user:display-name],\n\nThank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\n\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
register_pending_approval_admin:
  body: "[user:display-name] has applied for an account.\n\n[user:edit-url]"
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
status_activated:
  body: "[user:display-name],\n\nYour account at [site:name] has been activated.\n\nYou may now log in by clicking this link or copying and pasting it into your browser:\n\n[user:one-time-login-url]\n\nThis link can only be used once to log in and will lead you to a page where you can set your password.\n\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\n\nusername: [user:account-name]\npassword: Your password\n\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (approved)\'
status_blocked:
  body: "[user:display-name],\n\nYour account on [site:name] has been blocked.\n\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (blocked)\'
status_canceled:
  body: "[user:display-name],\n\nYour account on [site:name] has been canceled.\n\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (canceled)\'
langcode: en
', 'cancel_confirm:
  body: |
    [user:display-name],
    
    A request to cancel your account has been made at [site:name].
    
    You may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:
    
    [user:cancel-url]
    
    NOTE: The cancellation of your account is not reversible.
    
    This link expires in one day and nothing will happen if it is not used.
    
    --  [site:name] team
  subject: \'Account cancellation request for [user:display-name] at [site:name]\'
password_reset:
  body: |
    [user:display-name],
    
    A request to reset the password for your account has been made at [site:name].
    
    You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password. It expires after one day and nothing will happen if it\'s not used.
    
    --  [site:name] team
  subject: \'Replacement login information for [user:display-name] at [site:name]\'
register_admin_created:
  body: |
    [user:display-name],
    
    A site administrator at [site:name] has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:name]
    password: Your password
    
    --  [site:name] team
  subject: \'An administrator created an account for you at [site:name]\'
register_no_approval_required:
  body: |
    [user:display-name],
    
    Thank you for registering at [site:name]. You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:name]
    password: Your password
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name]\'
register_pending_approval:
  body: |
    [user:display-name],
    
    Thank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
register_pending_approval_admin:
  body: |
    [user:display-name] has applied for an account.
    
    [user:edit-url]
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
status_activated:
  body: |
    [user:display-name],
    
    Your account at [site:name] has been activated.
    
    You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:account-name]
    password: Your password
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (approved)\'
status_blocked:
  body: |
    [user:display-name],
    
    Your account on [site:name] has been blocked.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (blocked)\'
status_canceled:
  body: |
    [user:display-name],
    
    Your account on [site:name] has been canceled.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (canceled)\'
langcode: en
',
      ],
      /* @see core/modules/user/config/install/user.mail.yml */
      // Same example but after submitted via UI on macOS, CRLF instead LF.
      ['cancel_confirm:
  body: "[user:display-name],\r\n\r\nA request to cancel your account has been made at [site:name].\r\n\r\nYou may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:\r\n\r\n[user:cancel-url]\r\n\r\nNOTE: The cancellation of your account is not reversible.\r\n\r\nThis link expires in one day and nothing will happen if it is not used.\r\n\r\n--  [site:name] team"
  subject: \'Account cancellation request for [user:display-name] at [site:name]\'
password_reset:
  body: "[user:display-name],\r\n\r\nA request to reset the password for your account has been made at [site:name].\r\n\r\nYou may now log in by clicking this link or copying and pasting it into your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis link can only be used once to log in and will lead you to a page where you can set your password. It expires after one day and nothing will happen if it\'s not used.\r\n\r\n--  [site:name] team"
  subject: \'Replacement login information for [user:display-name] at [site:name]\'
register_admin_created:
  body: "[user:display-name],\r\n\r\nA site administrator at [site:name] has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis link can only be used once to log in and will lead you to a page where you can set your password.\r\n\r\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\r\n\r\nusername: [user:name]\r\npassword: Your password\r\n\r\n--  [site:name] team"
  subject: \'An administrator created an account for you at [site:name]\'
register_no_approval_required:
  body: "[user:display-name],\r\n\r\nThank you for registering at [site:name]. You may now log in by clicking this link or copying and pasting it into your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis link can only be used once to log in and will lead you to a page where you can set your password.\r\n\r\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\r\n\r\nusername: [user:name]\r\npassword: Your password\r\n\r\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name]\'
register_pending_approval:
  body: "[user:display-name],\r\n\r\nThank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.\r\n\r\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
register_pending_approval_admin:
  body: "[user:display-name] has applied for an account.\r\n\r\n[user:edit-url]"
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
status_activated:
  body: "[user:display-name],\r\n\r\nYour account at [site:name] has been activated.\r\n\r\nYou may now log in by clicking this link or copying and pasting it into your browser:\r\n\r\n[user:one-time-login-url]\r\n\r\nThis link can only be used once to log in and will lead you to a page where you can set your password.\r\n\r\nAfter setting your password, you will be able to log in at [site:login-url] in the future using:\r\n\r\nusername: [user:account-name]\r\npassword: Your password\r\n\r\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (approved)\'
status_blocked:
  body: "[user:display-name],\r\n\r\nYour account on [site:name] has been blocked.\r\n\r\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (blocked)\'
status_canceled:
  body: "[user:display-name],\r\n\r\nYour account on [site:name] has been canceled.\r\n\r\n--  [site:name] team"
  subject: \'Account details for [user:display-name] at [site:name] (canceled)\'
langcode: en
', 'cancel_confirm:
  body: |
    [user:display-name],
    
    A request to cancel your account has been made at [site:name].
    
    You may now cancel your account on [site:url-brief] by clicking this link or copying and pasting it into your browser:
    
    [user:cancel-url]
    
    NOTE: The cancellation of your account is not reversible.
    
    This link expires in one day and nothing will happen if it is not used.
    
    --  [site:name] team
  subject: \'Account cancellation request for [user:display-name] at [site:name]\'
password_reset:
  body: |
    [user:display-name],
    
    A request to reset the password for your account has been made at [site:name].
    
    You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password. It expires after one day and nothing will happen if it\'s not used.
    
    --  [site:name] team
  subject: \'Replacement login information for [user:display-name] at [site:name]\'
register_admin_created:
  body: |
    [user:display-name],
    
    A site administrator at [site:name] has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:name]
    password: Your password
    
    --  [site:name] team
  subject: \'An administrator created an account for you at [site:name]\'
register_no_approval_required:
  body: |
    [user:display-name],
    
    Thank you for registering at [site:name]. You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:name]
    password: Your password
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name]\'
register_pending_approval:
  body: |
    [user:display-name],
    
    Thank you for registering at [site:name]. Your application for an account is currently pending approval. Once it has been approved, you will receive another email containing information about how to log in, set your password, and other details.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
register_pending_approval_admin:
  body: |
    [user:display-name] has applied for an account.
    
    [user:edit-url]
  subject: \'Account details for [user:display-name] at [site:name] (pending admin approval)\'
status_activated:
  body: |
    [user:display-name],
    
    Your account at [site:name] has been activated.
    
    You may now log in by clicking this link or copying and pasting it into your browser:
    
    [user:one-time-login-url]
    
    This link can only be used once to log in and will lead you to a page where you can set your password.
    
    After setting your password, you will be able to log in at [site:login-url] in the future using:
    
    username: [user:account-name]
    password: Your password
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (approved)\'
status_blocked:
  body: |
    [user:display-name],
    
    Your account on [site:name] has been blocked.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (blocked)\'
status_canceled:
  body: |
    [user:display-name],
    
    Your account on [site:name] has been canceled.
    
    --  [site:name] team
  subject: \'Account details for [user:display-name] at [site:name] (canceled)\'
langcode: en
',
      ],
    ];
  }

}
