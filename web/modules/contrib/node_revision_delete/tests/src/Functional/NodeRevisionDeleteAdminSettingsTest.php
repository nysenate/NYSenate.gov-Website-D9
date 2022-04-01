<?php

namespace Drupal\Tests\node_revision_delete\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the node_revision_delete_admin_settings configuration form.
 *
 * @group node_revision_delete
 */
class NodeRevisionDeleteAdminSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_revision_delete', 'node'];

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configurationFileName;

  /**
   * The configuration file name.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Setting the config file.
    $this->configurationFileName = 'node_revision_delete.settings';
  }

  /**
   * Tests the configuration form, the permission and the link.
   */
  public function testConfigurationForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/content/node_revision_delete');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    // Creating a user with the module permission.
    $account = $this->drupalCreateUser(['administer node_revision_delete', 'access administration pages']);
    // Log in.
    $this->drupalLogin($account);

    // Checking the module link.
    $this->drupalGet('/admin/config/content');
    $this->assertSession()->linkByHrefExists('/admin/config/content/node_revision_delete');

    // @TODO Check the module local task.
    // Going to the config page.
    $this->drupalGet('/admin/config/content/node_revision_delete');
    // Checking that the request has succeeded.
    $this->assertSession()->statusCodeEquals(200);

    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'Node Revision Delete');
    // Check that the checkboxes are unchecked.
    $this->assertSession()->checkboxNotChecked('run_now');
    $this->assertSession()->checkboxNotChecked('dry_run');

    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');

    // Form values to send.
    $form_values = [
      [
        'delete_newer' => TRUE,
        'node_revision_delete_cron' => 20,
        'node_revision_delete_time' => 86400,
        'node_revision_delete_minimum_age_to_delete_time_max_number' => 10,
        'node_revision_delete_minimum_age_to_delete_time_time' => 'weeks',
        'node_revision_delete_when_to_delete_time_max_number' => 30,
        'node_revision_delete_when_to_delete_time_time' => 'days',
        'run_now' => FALSE,
        'dry_run' => FALSE,
      ],
      [
        'delete_newer' => FALSE,
        'node_revision_delete_cron' => 15,
        'node_revision_delete_time' => 7776000,
        'node_revision_delete_minimum_age_to_delete_time_max_number' => 20,
        'node_revision_delete_minimum_age_to_delete_time_time' => 'days',
        'node_revision_delete_when_to_delete_time_max_number' => 24,
        'node_revision_delete_when_to_delete_time_time' => 'months',
        'run_now' => FALSE,
        'dry_run' => FALSE,
      ],
    ];

    foreach ($form_values as $edit) {
      // Sending the form.
      $this->drupalPostForm(NULL, $edit, 'op');
      // Verifying the save message.
      $this->assertSession()->pageTextContains('The configuration options have been saved.');
      // Getting the configuration file.
      $config_file = $config_factory->get($this->configurationFileName);

      // Verifying the config values.
      $this->assertEquals($edit['delete_newer'], $config_file->get('delete_newer'));
      $this->assertEquals($edit['node_revision_delete_cron'], $config_file->get('node_revision_delete_cron'));
      $this->assertEquals($edit['node_revision_delete_time'], $config_file->get('node_revision_delete_time'));
      $this->assertEquals($edit['node_revision_delete_minimum_age_to_delete_time_max_number'], $config_file->get('node_revision_delete_minimum_age_to_delete_time')['max_number']);
      $this->assertEquals($edit['node_revision_delete_minimum_age_to_delete_time_time'], $config_file->get('node_revision_delete_minimum_age_to_delete_time')['time']);
      $this->assertEquals($edit['node_revision_delete_when_to_delete_time_max_number'], $config_file->get('node_revision_delete_when_to_delete_time')['max_number']);
      $this->assertEquals($edit['node_revision_delete_when_to_delete_time_time'], $config_file->get('node_revision_delete_when_to_delete_time')['time']);
    }
  }

}
