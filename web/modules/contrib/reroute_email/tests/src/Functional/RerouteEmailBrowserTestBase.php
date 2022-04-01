<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * @defgroup reroute_email_tests Test Suit
 * @{
 * The automated test suit for Reroute Email.
 * @}
 */

/**
 * Base test class for Reroute Email test cases.
 */
abstract class RerouteEmailBrowserTestBase extends BrowserTestBase {

  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $rerouteConfig;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reroute_email'];

  /**
   * Permissions required by the user to perform the tests.
   *
   * @var array
   */
  protected $permissions = [
    'administer reroute email',
  ];

  /**
   * User object to perform site browsing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Original email address used for the tests.
   *
   * @var string
   */
  protected $originalDestination = 'email@original-destination.com';

  /**
   * Reroute email destination address used for the tests.
   *
   * @var string
   */
  protected $rerouteDestination = 'email@reroute-destination.com';

  /**
   * Path for reroute email test form.
   *
   * @var string
   */
  protected $rerouteTestFormPath = 'admin/config/development/reroute_email/test';

  /**
   * Default subject value in the form.
   *
   * @var string
   */
  protected $rerouteFormDefaultSubject = 'Reroute Email Test';

  /**
   * Default subject value in the form.
   *
   * @var string
   */
  protected $rerouteFormDefaultBody = 'Reroute Email Body';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->rerouteConfig = $this->config('reroute_email.settings');

    // Authenticate test user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Helper function to configure Reroute Email Settings.
   *
   * An array of configuration options to set. All params are optional.
   * REROUTE_EMAIL_* define should be used as for array keys.
   * Default values can be found at reroute_email.schema.yml file.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function configureRerouteEmail($post_values): void {
    $schema_values = [
      REROUTE_EMAIL_ENABLE => FALSE,
      REROUTE_EMAIL_ADDRESS => '',
      REROUTE_EMAIL_ALLOWLIST => '',
      REROUTE_EMAIL_ROLES => [],
      REROUTE_EMAIL_DESCRIPTION => TRUE,
      REROUTE_EMAIL_MESSAGE => TRUE,
      REROUTE_EMAIL_MAILKEYS => '',
      REROUTE_EMAIL_MAILKEYS_SKIP => '',
    ];

    // Configure to Reroute Email settings form.
    foreach ($schema_values as $setting => $value) {
      $current_values[$setting] = $this->rerouteConfig->get($setting) ?? $value;
      $post_values[$setting] = $post_values[$setting] ?? $current_values[$setting];

      if (is_array($post_values[$setting])) {
        foreach ($post_values[$setting] as $val) {
          $post_values[$setting . "[{$val}]"] = $val;
        }
        unset($post_values[$setting]);
      }
    }

    // Submit Reroute Email Settings form and check if it was successful.
    $this->drupalGet('admin/config/development/reroute_email');
    $this->submitForm($post_values, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));

    // Rebuild config values after form submit.
    $this->rerouteConfig = $this->config('reroute_email.settings');
  }

  /**
   * Submit test email form and assert not rerouting.
   *
   * @param array $post
   *   An array of post data: 'to', 'cc', 'bcc', 'subject', 'body'.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertMailNotReroutedFromTestForm(array $post): void {
    $this->assertMailReroutedFromTestForm($post, FALSE);
  }

  /**
   * Submit test email form and assert rerouting.
   *
   * @param array $post
   *   An array of post data: 'to', 'cc', 'bcc', 'subject', 'body'.
   * @param bool $reroute_expected
   *   Expected reroute status.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertMailReroutedFromTestForm(array $post, bool $reroute_expected = TRUE): void {
    // Submit the test form.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->submitForm($post, $this->t('Send email'));
    $this->assertSession()->pageTextContains($this->t('Test email submitted for delivery from test form.'));

    // Get the most recent email.
    $mails = $this->getMails();
    $mail = end($mails);

    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $search_originally_to = sprintf('Originally to: %s', $post['to'] ?? '');

    // Check email properties related to `to` value.
    if ($reroute_expected) {
      $this->assertMail('to', $this->rerouteConfig->get(REROUTE_EMAIL_ADDRESS), new FormattableMarkup('An email was properly rerouted to the email address: @address.', ['@address' => $this->rerouteDestination]));
      $this->assertEquals($mail['headers']['X-Rerouted-Original-to'], $post['to'] ?? '', new FormattableMarkup('X-Rerouted-Original-to is correctly set to submitted value: @address', ['@address' => $post['to'] ?? '']));
      $this->assertMailString('body', $search_originally_to, 1, 'Found the correct "Originally to" line in the body.');
    }
    else {
      $this->assertMail('to', $post['to'] ?? '', new FormattableMarkup('An email was properly sent to the email address: @address.', ['@address' => $post['to']]));
      $this->assertArrayNotHasKey('X-Rerouted-Original-to', $mail['headers']);
      $this->assertStringNotContainsString($search_originally_to, $mail['body']);
    }

    // Check email subject.
    $this->assertMail('subject', $post['subject'] ?? $this->rerouteFormDefaultSubject, 'Subject is correctly set to submitted value: @subject');

    // Check email body can be found in the email.
    if (!empty($post['body'])) {
      $this->assertMailString('body', $post['body'], 1, 'Body contains the value submitted through the form.');
    }
    elseif (!isset($post['body'])) {
      $this->assertMailString('body', $this->rerouteFormDefaultBody, 1, 'Body contains the value submitted through the form.');
    }

    // Check the Cc and Bcc are the ones submitted through the form and were
    // added to the message body value.
    $this->assertMailReroutedHeaders('cc', $post['cc'] ?? NULL, $reroute_expected);
    $this->assertMailReroutedHeaders('bcc', $post['bcc'] ?? NULL, $reroute_expected);

    // Check reroute_mail module special headers.
    if ($this->rerouteConfig->get(REROUTE_EMAIL_ENABLE) === FALSE) {
      $this->assertMailHeaderNotExist('X-Rerouted-Status');
      $this->assertMailHeaderNotExist('X-Rerouted-Reason');
      $this->assertMailHeaderNotExist('X-Rerouted-Original-to');
      $this->assertMailHeaderNotExist('X-Rerouted-Original-cc');
      $this->assertMailHeaderNotExist('X-Rerouted-Original-bcc');
      $this->assertMailHeaderNotExist('X-Rerouted-Mail-Key');
      $this->assertMailHeaderNotExist('X-Rerouted-Website');
    }
    elseif ($reroute_expected) {
      $this->assertMailHeader('X-Rerouted-Status', 'REROUTED');
    }
    else {
      $this->assertMailHeader('X-Rerouted-Status', 'NOT-REROUTED');
      $this->assertMailHeaderExist('X-Rerouted-Reason');
    }
  }

  /**
   * Submit test email form and assert rerouting.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string|null $value
   *   An expected value of the header.
   * @param bool $rerouted
   *   Expected reroute status.
   */
  public function assertMailReroutedHeaders(string $header, ?string $value, bool $rerouted = TRUE): void {
    // Check email properties related to `to` value.
    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $header_body_search = sprintf('Originally %s: %s', $header, $value);
    $header_rerouted = 'X-Rerouted-Original-' . $header;

    // Get the most recent email.
    $mails = $this->getMails();
    $mail = end($mails);

    // Both rerouted and not rerouted mail should not have empty header.
    if (empty($value)) {
      $this->assertMailHeaderNotExist($header);
      $this->assertMailHeaderNotExist($header_rerouted);
      $this->assertStringNotContainsString($header_body_search, $mail['body']);
    }
    elseif ($rerouted === FALSE) {
      $this->assertMailHeader($header, $value);
      $this->assertMailHeaderNotExist($header_rerouted);
      $this->assertStringNotContainsString($header_body_search, $mail['body']);
    }
    elseif ($rerouted === TRUE) {
      $this->assertMailHeaderNotExist($header);
      $this->assertMailHeader($header_rerouted, $value);
      $this->assertMailString('body', $header_body_search, 1);
    }
  }

  /**
   * Asserts that the most recently sent email message has the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string|null $value
   *   An expected value of the header.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeader(string $header, ?string $value, string $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" is correctly set to submitted value: @value', [
        '@header' => $header,
        '@value' => $value,
      ]);
    }

    // Get the most recent email.
    $mails = $this->getMails();
    $mail = end($mails);
    $this->assertEquals($mail['headers'][$header], $value, $message);
  }

  /**
   * Asserts that the most recently sent email message has the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeaderExist(string $header, string $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" exist in the recent email.', [
        '@header' => $header,
      ]);
    }

    // Get the most recent email.
    $mails = $this->getMails();
    $mail = end($mails);
    $this->assertArrayHasKey($header, $mail['headers'], $message);
  }

  /**
   * Asserts that the most recently sent email message has not the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeaderNotExist(string $header, string $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" correctly does not exist in the recent email.', [
        '@header' => $header,
      ]);
    }

    // Get the most recent email.
    $mails = $this->getMails();
    $mail = end($mails);
    $this->assertArrayNotHasKey($header, $mail['headers'], $message);
  }

}
