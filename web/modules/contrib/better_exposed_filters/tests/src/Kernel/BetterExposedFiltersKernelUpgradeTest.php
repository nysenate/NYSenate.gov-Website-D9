<?php

namespace Drupal\Tests\better_exposed_filters\Kernel;

/**
 * Tests the update hooks to views configuration.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersKernelUpgradeTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests hiding the submit button when auto-submit is enabled.
   */
  public function testUpdate8004() {

    // Get the sample configuration for the bef_test view.
    $config_factory = \Drupal::configFactory();
    $bef_test_config = $config_factory->get('views.view.bef_test');
    $bef_advanced = $bef_test_config->get('display.default.display_options.exposed_form.options.bef.filter.status.advanced');

    // Check the state before update.
    $this->assertEquals([
      'sort_options' => FALSE,
      'placeholder_text' => '',
      'rewrite' => [
        'filter_rewrite_values' => '',
      ],
      'collapsible' => FALSE,
      'is_secondary' => FALSE,
    ], $bef_advanced);

    // Run the upgrade.
    \Drupal::moduleHandler()->loadInclude('better_exposed_filters', 'install');
    better_exposed_filters_update_8006();

    // Check that the state of the config after upgrade is correct.
    $bef_test_config = $config_factory->get('views.view.bef_test');
    $bef_advanced = $bef_test_config->get('display.default.display_options.exposed_form.options.bef.filter.status.advanced');

    // Ensure that the new option is added with a default of false.
    $this->assertEqualsCanonicalizing([
      'sort_options' => FALSE,
      'placeholder_text' => '',
      'rewrite' => [
        'filter_rewrite_values' => '',
      ],
      'collapsible' => FALSE,
      'collapsible_disable_automatic_open' => FALSE,
      'is_secondary' => FALSE,
    ], $bef_advanced);

    // Ensure that non-filters have been left untouched.
    $bef_advanced = $bef_test_config->get('display.default.display_options.exposed_form.options.bef.sort.advanced');
    $this->assertEquals([
      'combine' => FALSE,
      'combine_rewrite' => '',
      'reset' => FALSE,
      'reset_label' => '',
      'collapsible' => FALSE,
      'collapsible_label' => '',
      'is_secondary' => FALSE,
    ], $bef_advanced);
  }

}
