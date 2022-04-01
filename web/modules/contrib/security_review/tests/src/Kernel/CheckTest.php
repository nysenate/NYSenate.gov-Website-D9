<?php

namespace Drupal\Tests\security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\security_review\CheckResult;

/**
 * Contains tests for Checks.
 *
 * @group security_review
 */
class CheckTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['security_review', 'security_review_test'];

  /**
   * The security checks defined by Security Review and Security Review Test.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $checks;

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $realChecks;

  /**
   * The security checks defined by Security Review Test.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $testChecks;

  /**
   * Sets up the environment, populates the $checks variable.
   */
  protected function setUp() {
    parent::setUp();
    $this->realChecks = security_review_security_review_checks();
    $this->testChecks = security_review_test_security_review_checks();
    $this->checks = array_merge($this->realChecks, $this->testChecks);
  }

  /**
   * Tests whether $checks is empty.
   */
  public function testChecksExist() {
    $this->assertFalse(empty($this->checks), 'Checks found.');
  }

  /**
   * Every check should be enabled by default.
   */
  public function testEnabledByDefault() {
    foreach ($this->checks as $check) {
      $this->assertFalse($check->isSkipped(), $check->getTitle() . ' is enabled by default.');
    }
  }

  /**
   * Tests some check's results on a clean install of Drupal.
   */
  public function testDefaultResults() {
    $defaults = [
      'security_review-field' => CheckResult::SUCCESS,
    ];

    foreach ($this->checks as $check) {
      if (array_key_exists($check->id(), $defaults)) {
        $result = $check->run();
        $this->assertEquals($defaults[$check->id()], $result->result(), $check->getTitle() . ' produced the right result.');
      }
    }
  }

  /**
   * Tests the storing of a check result on every test check.
   */
  public function testStoreResult() {
    foreach ($this->testChecks as $check) {
      // Run the check and store its result.
      $result = $check->run();
      $check->storeResult($result);

      // Compare lastResult() with $result.
      $last_result = $check->lastResult(TRUE);
      $this->assertEquals($result->result(), $last_result->result(), 'Result stored.');
      $this->assertEquals($result->time(), $last_result->time(), 'Time stored.');
      if ($check->storesFindings()) {
        // If storesFindings() is set to FALSE, then these could differ.
        $this->assertEquals($result->findings(), $last_result->findings(), 'Findings stored.');
      }
    }
  }

  /**
   * Tests stored result correction on lastResult() call.
   *
   * Tests the case when the check doesn't store its findings, and the new
   * result that lastResult() returns overwrites the old one if the result
   * integer is not the same.
   */
  public function testLastResultUpdate() {
    foreach ($this->testChecks as $check) {
      if (!$check->storesFindings()) {
        // Get the real result.
        $result = $check->run();

        // Build the fake result.
        $new_result_result = $result->result() == CheckResult::SUCCESS ? CheckResult::FAIL : CheckResult::SUCCESS;
        $new_result = new CheckResult(
          $check,
          $new_result_result,
          [],
          TRUE
        );

        // Store it.
        $check->storeResult($new_result);

        // Check if lastResult()'s result integer is the same as $result's.
        $last_result = $check->lastResult(TRUE);
        $this->assertEquals($result->result(), $last_result->result(), 'Invalid result got updated.');
      }
    }
  }

}
