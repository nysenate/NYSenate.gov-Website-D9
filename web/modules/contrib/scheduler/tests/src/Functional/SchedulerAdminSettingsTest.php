<?php

namespace Drupal\Tests\scheduler\Functional;

/**
 * Tests the admin settings page of Scheduler.
 *
 * These tests only check that the admin page functions correctly. Other test
 * classes should be used to verify that the settings have the expected effects
 * on the scheduling operations and functionality.
 *
 * @group scheduler
 */
class SchedulerAdminSettingsTest extends SchedulerBrowserTestBase {

  /**
   * Test the admin settings page.
   */
  public function testAdminSettings() {
    $this->drupalLogin($this->adminUser);

    // Verify that the default values are as expected.
    $this->assertFalse($this->config('scheduler.settings')->get('allow_date_only'), 'The default setting for allow_date_only is False.');
    $this->assertEquals($this->config('scheduler.settings')->get('default_time'), '00:00:00', 'The default config setting for default_time is 00:00:00');
    $this->assertFalse($this->config('scheduler.settings')->get('hide_seconds'), 'The default setting for hide_seconds is False.');

    // Check that a default time can be stored, and that the option is saved.
    // In $settings use '6:30' not '06:30:00' to test flexibility.
    $settings = [
      'allow_date_only' => TRUE,
      'default_time' => '6:30',
    ];
    $this->drupalGet('admin/config/content/scheduler');
    $this->submitForm($settings, 'Save configuration');
    // Verify that the values have been saved correctly.
    $this->assertTrue($this->config('scheduler.settings')->get('allow_date_only'), 'The config setting for allow_date_only is stored correctly.');
    $this->assertEquals('06:30:00', $this->config('scheduler.settings')->get('default_time'), 'The config setting for default_time is stored correctly.');

    // Try to save an invalid default time value.
    $settings = [
      'allow_date_only' => TRUE,
      'default_time' => '123',
    ];
    $this->drupalGet('admin/config/content/scheduler');
    $this->submitForm($settings, 'Save configuration');
    // Verify that the value has not been saved and an error is displayed.
    $this->assertEquals('06:30:00', $this->config('scheduler.settings')->get('default_time'), 'The config setting for default_time has not changed.');
    $this->assertSession()->pageTextContains('The default time should be in the format HH:MM:SS');

    // Select the option to hide seconds on time input.
    $settings = [
      'hide_seconds' => TRUE,
    ];
    $this->drupalGet('admin/config/content/scheduler');
    $this->submitForm($settings, 'Save configuration');
    // Verify that the hide seconds option is saved and the default time is
    // stored in HH:MM format with no seconds.
    $this->assertTrue($this->config('scheduler.settings')->get('hide_seconds'), 'The config setting for hide_seconds is stored correctly.');
    $this->assertEquals('06:30', $this->config('scheduler.settings')->get('default_time'), 'The config setting for default_time is stored correctly.');

    // Try to save an invalid default time value.
    $settings = [
      'default_time' => '456',
    ];
    $this->drupalGet('admin/config/content/scheduler');
    $this->submitForm($settings, 'Save configuration');
    // Verify that the value has not been saved, and that an error message is
    // displayed showing the correct format HH:MM not HH:MM:SS.
    $this->assertEquals('06:30', $this->config('scheduler.settings')->get('default_time'), 'The config setting for default_time has not changed.');
    $this->assertSession()->pageTextMatches('/The default time should be in the format HH:MM[^:S]/');

    // Show the status report, which includes the Scheduler timecheck.
    $this->drupalGet('admin/reports/status');
  }

}
