<?php

namespace Drupal\Tests\contact_block\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Dummy Test.
 *
 * @group contact_block
 */
class DummyTest extends KernelTestBase {

  /**
   * Dummy test to run DrupalCI. Only to check if issue queue patches apply.
   */
  public function testDummy() {
    $this->assertTrue(TRUE, 'Assert TRUE.');
    $this->assertFalse(FALSE, 'Assert FALSE.');
  }

}
