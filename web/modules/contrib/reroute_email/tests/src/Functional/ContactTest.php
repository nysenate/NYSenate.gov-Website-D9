<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Test ability to reroute mail sent from the Contact module form.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class ContactTest extends RerouteEmailBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reroute_email', 'contact'];

  /**
   * Contact form confirmation message text.
   *
   * @var string
   */
  protected $confirmationMessage = 'Your message has been sent.';

  /**
   * Enable modules and create user with specific permissions.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the requested page status code is a different one.
   */
  public function setUp(): void {

    // Add more permissions to be able to manipulate the contact forms.
    $this->permissions[] = 'administer contact forms';
    $this->permissions[] = 'access site-wide contact form';
    parent::setUp();

    // Create a "feedback" contact form. Note that the 'message' was added in
    // the 8.2.x series, and is not there in 8.1.x, so this could fail in 8.1.x.
    $this->drupalGet('admin/structure/contact/add');
    $this->submitForm([
      'label' => 'feedback',
      'id' => 'feedback',
      'recipients' => $this->originalDestination,
      'message' => $this->confirmationMessage,
      'selected' => TRUE,
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that the flood controls don't break the test.
    \Drupal::service('config.factory')->getEditable('contact.settings')
      ->set('flood.limit', 1000)
      ->set('flood.interval', 60);
  }

  /**
   * Basic tests of email rerouting for emails sent through the Contact forms.
   *
   * The Core Contact email form is submitted several times with different
   * Email Rerouting settings: Rerouting enabled or disabled, Body injection
   * enabled or disabled, recipients from the allowed list and not.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the requested page status code is a different one.
   */
  public function testBasicNotification(): void {
    // Additional destination email address used for testing the allowed list.
    $additional_destination = 'additional@example.com';

    // Configure to reroute to {$this->rerouteDestination}.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
    ]);

    // Configure the contact settings to send to $original_destination.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->submitForm(['recipients' => $this->originalDestination], t('Save'));

    // Go to the contact page and send an email.
    $post = [
      'subject[0][value]' => 'Test test test',
      'message[0][value]' => 'This is a test',
    ];
    $this->drupalGet('contact');
    $this->submitForm($post, 'Send message');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->confirmationMessage);

    // Check rerouted email.
    $this->assertMail('to', $this->rerouteDestination, new FormattableMarkup('Email was rerouted to @address.', ['@address' => $this->rerouteDestination]));

    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $search_originally_to = sprintf('Originally to: %s', $this->originalDestination);
    $this->assertMailString('body', $search_originally_to, 1, 'Found the correct "Originally to" line in the body.');

    // Now try sending to one of the additional email addresses that should
    // not be rerouted. Configure two email addresses in reroute form.
    // Body injection is still turned on.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ALLOWLIST => "{$this->rerouteDestination}, {$additional_destination}",
    ]);

    // Configure the contact settings to point to the additional recipient.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->submitForm(['recipients' => $additional_destination], t('Save'));

    // Go to the contact page and send an email.
    $this->drupalGet('contact');
    $this->submitForm($post, t('Send message'));
    $this->assertSession()->pageTextContains($this->confirmationMessage);
    $this->assertMail('to', $additional_destination, 'Email was not rerouted because destination was in the allowed list.');

    // Now change the configuration to disable reroute and set the default
    // email recipients (from system.site.mail)
    $this->configureRerouteEmail([REROUTE_EMAIL_ENABLE => FALSE]);

    // Set the contact form to send to original_destination.
    $this->drupalGet('admin/structure/contact/manage/feedback');
    $this->submitForm(['recipients' => $this->originalDestination], t('Save'));

    // Go to the contact page and send an email.
    $this->drupalGet('contact');
    $this->submitForm($post, t('Send message'));
    $this->assertSession()->pageTextContains($this->confirmationMessage);

    // Mail should not be rerouted - should go to $original_destination.
    $this->assertMail('to', $this->originalDestination, 'Mail not rerouted - sent to original destination.');

    // Configure to reroute without body injection.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => TRUE,
      REROUTE_EMAIL_ALLOWLIST => '',
      REROUTE_EMAIL_DESCRIPTION => FALSE,
    ]);

    // Go to the contact page and send an email.
    $this->drupalGet('contact');
    $this->submitForm($post, t('Send message'));
    $this->assertSession()->pageTextContains($this->confirmationMessage);
    $mails = $this->getMails();
    $mail = end($mails);

    // There should be nothing in the body except the contact message - no
    // body injection like 'Originally to'.
    $this->assertStringNotContainsString('Originally to', $mail['body'], 'Body does not contain "Originally to".');
    $this->assertEquals($mail['headers']['X-Rerouted-Original-to'], $this->originalDestination, 'X-Rerouted-Original-to is correctly set to the original destination email.');
  }

}
