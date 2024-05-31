<?php

declare(strict_types = 1);

namespace Drupal\Tests\linkit\Kernel\CKEditor4To5Upgrade;

use Drupal\Tests\ckeditor5\Kernel\CKEditor4to5UpgradeCompletenessTest as Real;
use Drupal\KernelTests\KernelTestBase;

if (class_exists(Real::class)) {
  class CKEditor4to5UpgradeCompletenessTest extends Real { }
} else {
  class CKEditor4to5UpgradeCompletenessTest extends KernelTestBase {
    public function testImpossible() {
      $this->markTestSkipped();
    }
  }
}

/**
 * @covers \Drupal\linkit\Plugin\CKEditor4To5Upgrade\Linkit
 * @group linkit
 * @group ckeditor5
 * @internal
 * @requires module ckeditor5
 */
class UpgradePathCompletenessTest extends CKEditor4to5UpgradeCompletenessTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['linkit'];

}
