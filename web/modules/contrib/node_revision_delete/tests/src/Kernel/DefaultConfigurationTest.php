<?php

namespace Drupal\Tests\node_revision_delete\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the module configurations.
 *
 * @group node_revision_delete
 */
class DefaultConfigurationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_revision_delete'];

  /**
   * Tests the default configuration values.
   */
  public function testDefaultConfigurationValues() {
    // Installing the configuration file.
    $this->installConfig(self::$modules);
    // Getting the config file.
    $config_file = $this->container->get('config.factory')->get('node_revision_delete.settings');
    // Checking if delete_newer is FALSE.
    $this->assertFalse($config_file->get('delete_newer'), 'The default configuration value for delete_newer should be FALSE.');
    // Checking if the node_revision_delete_cron variable is equal 50.
    $this->assertEquals(50, $config_file->get('node_revision_delete_cron'));
    // Checking if the node_revision_delete_time variable is equal -1.
    $this->assertEquals(-1, $config_file->get('node_revision_delete_time'));
    // Checking the node_revision_delete_when_to_delete_time variable.
    $this->assertEquals(['max_number' => 12, 'time' => 'months'], $config_file->get('node_revision_delete_when_to_delete_time'));
    // Checking the node_revision_delete_minimum_age_to_delete_time variable.
    $this->assertEquals(['max_number' => 12, 'time' => 'months'], $config_file->get('node_revision_delete_minimum_age_to_delete_time'));
  }

}
