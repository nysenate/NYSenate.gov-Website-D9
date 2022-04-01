<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email's form for sending a test email.
 *
 * @ingroup reroute_email_tests
 *
 * @group reroute_email
 */
class TestEmailFormTest extends RerouteEmailBrowserTestBase {

  /**
   * Basic tests for reroute_email Test Email form.
   *
   * Check if submitted form values are properly submitted and rerouted.
   * Test Subject, To, Cc, Bcc and Body submitted values, form validation,
   * default values, and submission with invalid email addresses.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @dataProvider testFormValuesProvider
   */
  public function testFormTestEmail($enabled, $allowlisted, $post, $rerouted): void {

    // Configure to reroute all outgoing emails.
    $this->configureRerouteEmail([
      REROUTE_EMAIL_ENABLE => $enabled,
      REROUTE_EMAIL_ADDRESS => $this->rerouteDestination,
      REROUTE_EMAIL_ALLOWLIST => $allowlisted,
    ]);

    // Check Subject field default value.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->assertSession()->fieldValueEquals('subject', $this->rerouteFormDefaultSubject);
    $this->assertSession()->fieldValueEquals('body', $this->rerouteFormDefaultBody);

    $this->assertMailReroutedFromTestForm($post, $rerouted);
  }

  /**
   * Data provider for ::testFormTestEmail().
   */
  public function testFormValuesProvider(): array {

    // All fields are set correctly.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => $this->originalDestination,
        'cc' => $this->randomMachineName() . '@not-allowed.com',
        'bcc' => $this->randomMachineName() . '@not-allowed.com',
        'subject' => 'Test Reroute Email Test Email Form',
        'body' => 'Testing email rerouting and the Test Email form',
      ],
      'rerouted' => TRUE,
    ];

    // A test with invalid emails and default values for subject and body.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => 'To address invalid format',
        'cc' => 'Cc address invalid format',
        'bcc' => 'Bcc address invalid format',
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => FALSE,
      'allowlisted' => '',
      'post' => [
        'to' => 'To address invalid format',
        'cc' => 'Cc address invalid format',
        'bcc' => 'Bcc address invalid format',
      ],
      'rerouted' => FALSE,
    ];

    // Test a form with empty values for non-required fields.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => '',
        'cc' => '',
        'bcc' => '',
        'subject' => '',
        'body' => '',
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => "{$this->originalDestination}, ",
      'post' => [
        'to' => $this->originalDestination,
        'cc' => '',
        'bcc' => '',
        'subject' => '',
        'body' => '',
      ],
      'rerouted' => FALSE,
    ];

    // Tests for partial emails amd domain wildcards in the allowed list.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => 'some+*@allowlisted.com',
      'post' => ['to' => 'email@allowlisted.com'],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => 'some+*@allowlisted.com',
      'post' => ['to' => 'some+partial@allowlisted.com'],
      'rerouted' => FALSE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => 'myname@*, *@great-company.com',
      'post' => ['to' => 'myname@allowed.com, email@great-company.com'],
      'rerouted' => FALSE,
    ];

    // Check if recipient fields support an email with additional display name.
    // like "Display Name <display.name@example.com>".
    $email_allowlisted_one = $this->randomMachineName() . '@allowlisted.com';
    $email_allowlisted_two = $this->randomMachineName() . '@allowlisted.com';
    $email_allowlisted_three = $this->randomMachineName() . '@allowlisted.com';
    $email_allowlisted_not = $this->randomMachineName() . '@not-allowlisted.com';
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => "{$email_allowlisted_one}, {$email_allowlisted_two}",
      'post' => [
        'to' => "Some Display Name <{$email_allowlisted_not}>",
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => "{$email_allowlisted_one}, {$email_allowlisted_two}, {$email_allowlisted_three}",
      'post' => [
        'to' => "Display Name <{$email_allowlisted_one}>",
        'cc' => "Display Name &*% (Test Special Chars) <{$email_allowlisted_two}>",
        'bcc' => "Display Name @ <{$email_allowlisted_three}>",
      ],
      'rerouted' => FALSE,
    ];

    // Check rerouting by `cc` and `bcc` with allowlisted `to` value.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => $email_allowlisted_one,
        'cc' => $email_allowlisted_two,
        'bcc' => $email_allowlisted_three,
      ],
      'rerouted' => FALSE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => $email_allowlisted_one,
        'cc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'cc' => $email_allowlisted_one,
        'bcc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => '',
        'cc' => $email_allowlisted_not,
        'bcc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];

    return $data;
  }

}
