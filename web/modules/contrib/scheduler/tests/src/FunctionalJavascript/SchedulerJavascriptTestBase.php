<?php

namespace Drupal\Tests\scheduler\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\scheduler\Traits\SchedulerCommerceProductSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerMediaSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerSetupTrait;
use Drupal\Tests\scheduler\Traits\SchedulerTaxonomyTermSetupTrait;

/**
 * Base class for Scheduler javascript tests.
 */
abstract class SchedulerJavascriptTestBase extends WebDriverTestBase {

  use SchedulerCommerceProductSetupTrait;
  use SchedulerMediaSetupTrait;
  use SchedulerSetupTrait;
  use SchedulerTaxonomyTermSetupTrait;

  /**
   * The standard modules to load for all javascript tests.
   *
   * Additional modules can be specified in the tests that need them.
   *
   * @var array
   */
  protected static $modules = [
    'scheduler',
    'media',
    'commerce_product',
    'taxonomy',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Call the common set-up functions defined in the traits.
    $this->schedulerSetUp();
    // $this->getName() includes the test class and the dataProvider key. We can
    // use this to save time and resources by avoiding calls to the media and
    // product setup functions when they are not needed.
    if (stristr($this->getName(), 'media')) {
      $this->schedulerMediaSetUp();
    }
    if (stristr($this->getName(), 'product')) {
      $this->SchedulerCommerceProductSetUp();
    }
    if (stristr($this->getName(), 'taxonomy')) {
      $this->SchedulerTaxonomyTermSetup();
    }
  }

  /**
   * Flush cache.
   */
  protected function flushCache() {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('cache_flush');
  }

}
