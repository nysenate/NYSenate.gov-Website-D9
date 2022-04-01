<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Test handling of unusual fields.
 *
 * - message body passed as a string
 * - Cc/Bcc header keys with an unexpected case.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class UnusualMessageFieldsTest extends RerouteEmailBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reroute_email', 'reroute_email_test', 'dblog'];

  /**
   * Enable modules and create user with specific permissions.
   */
  protected function setUp(): void {
    // Add more permissions to access recent log messages in test.
    $this->permissions[] = 'access site reports';

    // Include hidden test helper sub-module.
    parent::setUp();
  }

  /**
   * Test handling of message body as a string and header keys' robustness.
   *
   * A test email is sent by the reroute_email_test module with a string for
   * the body of the email message and Cc/Bcc header keys with an unexpected
   * case. Test if Reroute Email handles message's body properly when it is a
   * string and captures all Cc/Bcc header keys independently of the case.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBodyStringRobustHeaders(): void {
    // Initialize Cc and Bcc keys with a special case.
    $test_cc_key = 'cC';
    $test_bcc_key = 'bCc';

    // Configure to reroute to {$this->rerouteDestination}.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
    ]);

    // Print test email values for comparing values on test results page.
    $test_message = [
      'to' => $this->originalDestination,
      'params' => [
        'body' => 'Test Message body is a string.',
        'headers' => [
          'test_cc_key' => $test_cc_key,
          'test_bcc_key' => $test_bcc_key,
          $test_cc_key => 'test_cc_key@example.com',
          $test_bcc_key => 'test_bcc_key@example.com',
        ],
      ],
    ];
    // Send test helper sub-module's email.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    \Drupal::getContainer()
      ->get('plugin.manager.mail')
      ->mail('reroute_email_test', 'test_reroute_email', $test_message['to'], $langcode, $test_message['params']);

    $mails = $this->getMails();
    $mail = end($mails);

    // Check rerouted email to.
    $this->assertMail('to', $this->rerouteDestination, new FormattableMarkup('To email address was rerouted to @address.', ['@address' => $this->rerouteDestination]));

    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $search_originally_to = sprintf('Originally to: %s', $this->originalDestination);
    $this->assertMailString('body', $search_originally_to, 1, 'Found the correct "Originally to" line in the body.');

    // Check if test message body is found although provided as a string.
    $this->assertStringContainsString($test_message['params']['body'], $mail['body'], 'Email body contains original message body although it was provided as a string.');

    // Check the watchdog entry logged by reroute_email_test_mail_alter.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->responseContains(t('A String was detected in the body'));

    // Test the robustness of the CC and BCC keys in headers.
    $this->assertEquals($mail['headers']['X-Rerouted-Original-cc'], $test_message['params']['headers'][$test_cc_key], new FormattableMarkup('X-Rerouted-Original-cc is correctly set to @test_cc_address, although Cc header message key provided was: @test_cc_key', [
      '@test_cc_address' => $test_message['params']['headers'][$test_cc_key],
      '@test_cc_key' => $test_cc_key,
    ]));
    $this->assertEquals($mail['headers']['X-Rerouted-Original-bcc'], $test_message['params']['headers'][$test_bcc_key], new FormattableMarkup('X-Rerouted-Original-bcc is correctly set to @test_bcc_address, although Bcc header message key provided was: @test_bcc_key', [
      '@test_bcc_address' => $test_message['params']['headers'][$test_bcc_key],
      '@test_bcc_key' => $test_bcc_key,
    ]));
  }

}
