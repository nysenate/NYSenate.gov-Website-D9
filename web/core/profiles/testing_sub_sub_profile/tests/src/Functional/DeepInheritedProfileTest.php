<?php

namespace Drupal\Tests\testing_sub_sub_profile\Functional;

use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests installing from an inherited profile whose parent is also inherited.
 *
 * @group profiles
 */
class DeepInheritedProfileTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_sub_sub_profile';

  /**
   * Tests sub-sub-profile inherited installation.
   */
  public function testDeepInheritedProfile() {
    // Check that stable is the default theme enabled in parent profile.
    $this->assertSame('stable', $this->config('system.theme')->get('default'));

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    // page_cache was enabled in main profile.
    $this->assertTrue($module_handler->moduleExists('page_cache'));
    // block was enabled in parent profile.
    $this->assertTrue($module_handler->moduleExists('block'));
    // syslog was enabled in this profile.
    $this->assertTrue($module_handler->moduleExists('syslog'));
    // A module contained in this profile was installed too.
    $this->assertTrue($module_handler->moduleExists('grandchild_profile_module'));
  }

}
