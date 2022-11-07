<?php

namespace Drupal\Tests\fontawesome\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Font Awesome configuration.
 *
 * @group fontawesome
 */
class FontawesomeMigrationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fontawesome',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('fontawesome'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests Font Awesome settings migration.
   */
  public function testFontAwesomeMigration(): void {
    $expected_config = [
      'method' => 'svg',
      'allow_pseudo_elements' => TRUE,
      'use_cdn' => TRUE,
      'external_svg_location' => 'https://use.fontawesome.com/releases/v5.11.2/js/all.js',
      'use_solid_file' => TRUE,
      'use_regular_file' => TRUE,
      'use_light_file' => TRUE,
      'use_brands_file' => TRUE,
      'use_shim' => TRUE,
      'external_shim_location' => 'docroot/library/fontawesome',
    ];
    $config_before = $this->config('fontawesome.settings')->getRawData();
    $this->assertNotEquals($expected_config, $config_before);
    $this->executeMigrations(['fontawesome_settings']);
    $config_after = $this->config('fontawesome.settings')->getRawData();
    $this->assertEquals($expected_config, $config_after);
  }

}
