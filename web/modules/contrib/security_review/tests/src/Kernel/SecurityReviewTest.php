<?php

namespace Drupal\Tests\security_review\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Contains tests related to the SecurityReview class.
 *
 * @group security_review
 */
class SecurityReviewTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['security_review'];

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * Sets up the testing environment.
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->securityReview = \Drupal::getContainer()->get('security_review');
  }

  /**
   * Tests the 'logging' setting.
   */
  public function testConfigLogging() {
    $this->assertTrue($this->securityReview->isLogging(), 'Logging enabled by default.');
    $this->securityReview->setLogging(FALSE);
    $this->assertFalse($this->securityReview->isLogging(), 'Logging disabled.');
  }

  /**
   * Tests the 'configured' setting.
   */
  public function testConfigConfigured() {
    $this->assertFalse($this->securityReview->isConfigured(), 'Not configured by default.');
    $this->securityReview->setConfigured(TRUE);
    $this->assertTrue($this->securityReview->isConfigured(), 'Set to configured.');
  }

  /**
   * Tests the 'untrusted_roles' setting.
   */
  public function testConfigUntrustedRoles() {
    $this->assertEquals([], $this->securityReview->getUntrustedRoles(), 'untrusted_roles empty by default.');

    $roles = [0, 1, 2, 3, 4];
    $this->securityReview->setUntrustedRoles($roles);
    $this->assertEquals($roles, $this->securityReview->getUntrustedRoles(), 'untrusted_roles set to test array.');
  }

  /**
   * Tests the 'last_run' setting.
   */
  public function testConfigLastRun() {
    $this->assertEquals(0, $this->securityReview->getLastRun(), 'last_run is 0 by default.');
    $time = time();
    $this->securityReview->setLastRun($time);
    $this->assertEquals($time, $this->securityReview->getLastRun(), 'last_run set to now.');
  }

}
