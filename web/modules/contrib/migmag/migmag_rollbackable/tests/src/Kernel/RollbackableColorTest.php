<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\migrate\MigrateExecutable;

/**
 * Tests the rollbackability of theme color settings destination.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableColor
 *
 * @group migmag_rollbackable
 */
class RollbackableColorTest extends RollbackableDestinationTestBase {

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const COLOR_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'config_prefix' => 'color.theme.',
      'data_rows' => [
        [
          'element_name' => 'files',
          'value' => [
            'public://color/bartik-112137/logo.png',
            'public://color/bartik-112137/colors.css',
          ],
          'theme_name' => 'bartik',
        ],
        [
          'element_name' => 'logo',
          'value' => 'public://color/bartik-112137/logo.png',
          'theme_name' => 'bartik',
        ],
        [
          'element_name' => 'palette',
          'value' => [
            'bg' => '#112137',
            'fg' => '#ffffff',
          ],
          'theme_name' => 'bartik',
        ],
        [
          'element_name' => 'stylesheets',
          'value' => [
            'public://color/bartik-112137/colors.css',
          ],
          'theme_name' => 'bartik',
        ],
      ],
      'ids' => [
        'element_name' => ['type' => 'string'],
        'theme_name' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'element_name' => 'element_name',
      'configuration_name' => [
        'plugin' => 'concat',
        'source' => [
          'config_prefix',
          'theme_name',
        ],
      ],
      'value' => 'value',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_color',
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * Access level should be public for Drupal core 8.9.x.
   */
  public static $modules = [
    'color',
  ];

  /**
   * Tests the rollbackability of color settings destination.
   *
   * @dataProvider providerTestMigrationRollback
   */
  public function testColorRollback(bool $with_preexisting_config = TRUE) {
    $this->container->get('theme_installer')->install(['bartik', 'seven']);
    $bartik_color = $this->config('color.theme.bartik');
    $seven_color = $this->config('color.theme.seven');
    $this->assertTrue($bartik_color->isNew());
    $this->assertTrue($seven_color->isNew());

    if ($with_preexisting_config) {
      $bartik_color->set('logo', 'public://color/initial-dummy-logo.png')->save();
      $seven_color->set('logo', 'public://color/initial-dummy-logo.png')->save();
    }

    $bartik_color_data_before_migration = $bartik_color->getRawData();
    $seven_color_data_before_migration = $seven_color->getRawData();

    // Execute the base migration.
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    $expected_bartik_color_after_base_migration = [
      'files' => [
        'public://color/bartik-112137/logo.png',
        'public://color/bartik-112137/colors.css',
      ],
      'logo' => 'public://color/bartik-112137/logo.png',
      'palette' => [
        'bg' => '#112137',
        'fg' => '#ffffff',
      ],
      'stylesheets' => [
        'public://color/bartik-112137/colors.css',
      ],
    ];

    $this->assertEquals(
      $expected_bartik_color_after_base_migration,
      $this->config('color.theme.bartik')->getRawData()
    );

    if (!$with_preexisting_config) {
      $this->assertTrue($this->config('color.theme.seven')->isNew());
    }

    // Execute an another migration which updates some of the previous targets.
    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_bartik_color_after_subsequent_migration = $expected_bartik_color_after_base_migration;
    $expected_bartik_color_after_subsequent_migration['palette'] = [
      'bg' => '#ffffff',
      'fg' => '#112137',
    ];

    $this->assertEquals(
      $expected_bartik_color_after_subsequent_migration,
      $this->config('color.theme.bartik')->getRawData()
    );

    $this->assertEquals(
      [
        'files' => [
          'public://color/seven-34f55321/logo.png',
        ],
        'logo' => 'public://color/seven-34f55321/logo.png',
      ],
      $this->config('color.theme.seven')->getRawData()
    );

    // Roll back the last migration.
    $subsequent_executable->rollback();

    if (!$with_preexisting_config) {
      $this->assertFalse($this->config('color.theme.bartik')->isNew());
      $this->assertTrue($this->config('color.theme.seven')->isNew());
    }
    $this->assertEquals(
      $expected_bartik_color_after_base_migration,
      $this->config('color.theme.bartik')->getRawData()
    );
    $this->assertEquals(
      $seven_color_data_before_migration,
      $this->config('color.theme.seven')->getRawData()
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $bartik_color_data_before_migration,
      $this->config('color.theme.bartik')->getRawData()
    );
    $this->assertEquals(
      $seven_color_data_before_migration,
      $this->config('color.theme.seven')->getRawData()
    );

    if ($with_preexisting_config) {
      $this->assertFalse($this->config('color.theme.bartik')->isNew());
      $this->assertFalse($this->config('color.theme.seven')->isNew());
    }
    else {
      $this->assertTrue($this->config('color.theme.bartik')->isNew());
      $this->assertTrue($this->config('color.theme.seven')->isNew());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration() {
    $definition = ['id' => 'color_base'] + self::COLOR_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration() {
    $definition = self::COLOR_MIGRATION_BASE;
    $definition['id'] = 'color_subsequent';
    $definition['source']['data_rows'] = [
      [
        'element_name' => 'palette',
        'value' => [
          'bg' => '#ffffff',
          'fg' => '#112137',
        ],
        'theme_name' => 'bartik',
      ],
      [
        'element_name' => 'files',
        'value' => [
          'public://color/seven-34f55321/logo.png',
        ],
        'theme_name' => 'seven',
      ],
      [
        'element_name' => 'logo',
        'value' => 'public://color/seven-34f55321/logo.png',
        'theme_name' => 'seven',
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration() {
    // Color destination cannot have translation destination.
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration() {
    // Color destination cannot have translation destination.
  }

}
