<?php

namespace Drupal\Tests\testing_inherited\Functional;

use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests installing from an inherited profile.
 *
 * @group profiles
 */
class InheritedProfileTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_inherited';

  /**
   * Tests inherited installation profile.
   */
  public function testInheritedProfile() {
    // Check that the stable_login block exists.
    $this->assertInstanceOf(BlockInterface::class, Block::load('stable_login'));

    // Check that stable is the default theme.
    $this->assertSame('stable', $this->config('system.theme')->get('default'));

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    // Check that parent dependencies are installed.
    $this->assertTrue($module_handler->moduleExists('page_cache'));
    // Check that child profile dependencies are installed.
    $this->assertTrue($module_handler->moduleExists('config'));
    // Check that modules contained in the child profile are installed.
    $this->assertTrue($module_handler->moduleExists('child_profile_module'));
    $this->assertTrue($module_handler->moduleExists('contrib_child_profile_module'));
    $this->assertTrue($module_handler->moduleExists('custom_child_profile_module'));

    // Check that all themes were installed.
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('stable'));
  }

}
