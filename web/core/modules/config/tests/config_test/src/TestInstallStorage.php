<?php

namespace Drupal\config_test;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Tests configuration of profiles, modules and themes.
 *
 * A test configuration storage to read configuration from all profiles, modules
 * and themes regardless of installation status or installed profile.
 */
class TestInstallStorage extends InstallStorage {

  /**
   * {@inheritdoc}
   */
  protected function getAllFolders() {
    if (!isset($this->folders)) {
      $this->folders = $this->getCoreNames();
      $listing = new ExtensionDiscovery(\Drupal::root());
      $listing->setProfileDirectories([]);
      // @todo Remove as part of https://www.drupal.org/node/2186491
      $this->folders = $this->getComponentNames($listing->scan('profile')) + $this->folders;
      $this->folders = $this->getComponentNames($listing->scan('module')) + $this->folders;
      $this->folders = $this->getComponentNames($listing->scan('theme')) + $this->folders;
    }
    return $this->folders;
  }

}
