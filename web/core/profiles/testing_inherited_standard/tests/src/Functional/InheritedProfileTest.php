<?php

namespace Drupal\Tests\testing_inherited_standard\Functional;

use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests installing from an inherited standard profile.
 *
 * @group profiles
 */
class InheritedProfileTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_inherited_standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Tests inherited installation profile.
   */
  public function testInheritedProfile() {
    // Do nothing, simply install this profile.
  }

}
