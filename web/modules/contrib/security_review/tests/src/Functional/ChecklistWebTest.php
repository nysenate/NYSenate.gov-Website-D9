<?php

namespace Drupal\Tests\security_review\Functional;

use Drupal\security_review\Checklist;
use Drupal\Tests\BrowserTestBase;

/**
 * Contains tests related to the SecurityReview class.
 *
 * @group security_review
 */
class ChecklistWebTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'security_review',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $checks;

  /**
   * The security_review.checklist service.
   *
   * @var \Drupal\security_review\Checklist
   */
  protected $checklist;

  /**
   * Sets up the testing environment.
   */
  protected function setUp() {
    parent::setUp();

    $this->checklist = \Drupal::getContainer()
      ->get('security_review.checklist');

    // Login.
    $this->user = $this->drupalCreateUser(
      [
        'run security checks',
        'access security review list',
        'access administration pages',
        'administer site configuration',
      ]
    );
    $this->drupalLogin($this->user);

    // Populate $checks.
    $this->checks = security_review_security_review_checks();

    // Clear cache.
    Checklist::clearCache();
  }

  /**
   * Tests a full checklist run.
   *
   * Tests whether the checks hasn't been run yet, then runs them and checks
   * that their lastRun value is not 0.
   */
  public function testRun() {
    foreach ($this->checks as $check) {
      $this->assertEqual(0, $check->lastRun(), $check->getTitle() . ' has not been run yet.');
    }
    $this->checklist->runChecklist();
    foreach ($this->checks as $check) {
      $this->assertNotEqual(0, $check->lastRun(), $check->getTitle() . ' has been run.');
    }
  }

  /**
   * Skips all checks then runs the checklist. No checks should be ran.
   */
  public function testSkippedRun() {
    foreach ($this->checks as $check) {
      $check->skip();
    }
    $this->checklist->runChecklist();
    foreach ($this->checks as $check) {
      $this->assertEqual(0, $check->lastRun(), $check->getTitle() . ' has not been run.');
    }
  }

}
