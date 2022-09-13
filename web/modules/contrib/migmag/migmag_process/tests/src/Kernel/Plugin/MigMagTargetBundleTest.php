<?php

namespace Drupal\Tests\migmag_process\Kernel\Plugin;

use Drupal\migmag_process\Plugin\migrate\process\MigMagTargetBundle;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests the MigMagTargetBundle migrate process plugin with real migrations.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagTargetBundle
 *
 * @group migmag_process
 */
class MigMagTargetBundleTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   *
   * Access level should be public for Drupal core 8.9.x.
   */
  public static $modules = [
    'entity_test',
    'migmag_process',
    'migmag_target_bundle_test',
    'taxonomy',
  ];

  /**
   * Tests the MigMagTargetBundle migrate process plugin.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPlugin
   */
  public function testPlugin($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    $migration = $this->prophesize(MigrationInterface::class);
    $executable = $this->prophesize(MigrateExecutable::class);
    $row_data_source = $row_data['source'] ?? [
      'dummy_source_property' => 'dummy_source_data',
    ];
    $row = new Row(
      $row_data_source,
      array_combine(array_keys($row_data_source), array_keys($row_data_source))
    );
    foreach ($row_data['destination'] ?? [] as $destination_property => $destination_value) {
      $row->setDestinationProperty($destination_property, $destination_value);
    }

    if ($migrations_to_execute) {
      $this->startCollectingMessages();
      $this->executeMigrations((array) $migrations_to_execute);
      $this->assertEmpty($this->migrateMessages);
    }

    $plugin = MigMagTargetBundle::create(
      $this->container,
      $plugin_configuration,
      'migmag_target_bundle',
      [],
      $migration->reveal()
    );
    $actual_transformed_value = $plugin->transform($value, $executable->reveal(), $row, 'destination_property');
    $this->assertEquals(
      $expected_transformed_value,
      $actual_transformed_value
    );
  }

  /**
   * Test the plugin with comment types.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPluginWithCommentTypes
   */
  public function testPluginWithCommentTypes($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    $this->enableModules([
      'comment',
      'field',
      'filter',
      'text',
      'system',
    ]);
    $this->installConfig(['comment']);

    self::testPlugin($value, $row_data, $expected_transformed_value, $plugin_configuration, $migrations_to_execute);
  }

  /**
   * Test the plugin with combined config, including source & destination types.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPluginWithCombinedConfig
   */
  public function testPluginWithCombinedConfig($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    self::testPlugin($value, $row_data, $expected_transformed_value, $plugin_configuration, $migrations_to_execute);
  }

  /**
   * Data provider for ::testPlugin.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestPlugin() {
    return [
      'Not yet migrated taxonomy vocabulary, with fallback' => [
        'Value to transform' => 'missing',
        'Row' => NULL,
        'Expected' => 'missing',
        'Config' => [
          'null_if_missing' => FALSE,
        ],
      ],

      'Not yet migrated taxonomy vocabulary without fallback' => [
        'Value to transform' => 'vocabulary 1',
        'Row' => NULL,
        'Expected' => NULL,
        'Config' => [
          'null_if_missing' => TRUE,
        ],
      ],

      'Entity test bundle with non-matching source entity type, no fallback' => [
        'Value to transform' => 'bundle 1',
        'Row' => [
          'source' => ['source_prop' => 'non-matching-source-type'],
        ],
        'Expected' => NULL,
        'Config' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_entity_test',
      ],

      'Entity test bundle with missing source entity type, no fallback' => [
        'Value to transform' => 'bundle 1',
        'Row' => [
          'source' => ['othersource_prop' => 1],
        ],
        'Expected' => NULL,
        'Config' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_entity_test',
      ],

      'Entity test bundle, no fallback, without lookup migrations config' => [
        'Value to transform' => 'bundle 1',
        'Row' => [
          'source' => ['source_prop' => 'entity_test_with_bundle'],
        ],
        'Expected' => NULL,
        'Config' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_entity_test',
      ],

      "Taxonomy vocabulary 'vocabulary 1', no fallback, single lookup migration" => [
        'Value to transform' => 'vocabulary 1',
        'Row' => [
          'source' => ['source_prop' => 'taxonomy_vocabulary'],
        ],
        'Expected' => 'vocabulary_1',
        'Config' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
          'source_lookup_migrations' => [
            'taxonomy_vocabulary' => 'migmag_tbt_vocabulary',
          ],
        ],
        'Migrations to execute' => 'migmag_tbt_vocabulary',
      ],

      "Taxonomy vocabulary 'derivative 2 vocab' with derived lookup migrations" => [
        'Value to transform' => 'derivative 2 vocab',
        'Row' => [
          'source' => ['source_prop' => 'taxonomy_vocabulary'],
        ],
        'Expected' => 'derivative_2_vocab',
        'Config' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
          'source_lookup_migrations' => [
            'taxonomy_vocabulary' => [
              'migmag_tbt_vocabulary',
              'migmag_tbt_vocabulary_derived',
            ],
          ],
        ],
        'Migrations to execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with non-matching custom destination entity type conf" => [
        'Value to transform' => 'bundle 2',
        'Row' => [
          'destination' => ['destination_prop' => 'taxonomy_vocabulary'],
        ],
        'Expected' => NULL,
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with default destination entity type conf" => [
        'Value to transform' => 'bundle 2',
        'Row' => [
          'destination' => ['entity_type' => 'taxonomy_vocabulary'],
        ],
        'Expected' => NULL,
        'Config' => [
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with matching destination entity type" => [
        'Value to transform' => 'bundle 2',
        'Row' => [
          'destination' => ['destination_prop' => 'entity_test_with_bundle'],
        ],
        'Expected' => 'bundle_2',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' with matching destination entity type" => [
        'Value to transform' => 'vocabulary 2',
        'Row' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => 'vocabulary_2',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Taxonomy vocabulary 'derivative 1 vocab 2' with matching destination entity type" => [
        'Value to transform' => 'derivative 1 vocab 2',
        'Row' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => 'derivative_1_vocab_2',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithCommentTypes.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestPluginWithCommentTypes() {
    return [
      "Comment bundle 'test_type' looking for 'test_type'" => [
        'Value to transform' => 'test_type',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_node_test_type',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'test_type' looking for 'comment_node_test_type'" => [
        'Value to transform' => 'comment_node_test_type',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_node_test_type',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'comment_node_test' looking for 'comment_node_test'" => [
        'Value to transform' => 'comment_node_test',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_node_comment_node_test',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'comment_node_test' looking for 'comment_node_comment_node_test'" => [
        'Value to transform' => 'comment_node_comment_node_test',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_node_comment_node_test',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'forum' looking for 'forum'" => [
        'Value to transform' => 'forum',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_forum',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'forum' looking for 'comment_node_forum'" => [
        'Value to transform' => 'comment_node_forum',
        'Row' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'Expected' => 'comment_forum',
        'Config' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => 'migmag_tbt_comment',
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithCombinedConfig.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestPluginWithCombinedConfig() {
    return [
      "Migrations used in source lookup should be excluded with destination entity type" => [
        'Value to transform' => 'vocabulary 2',
        'Row' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => NULL,
        'Config' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => [
              'migmag_tbt_vocabulary_derived',
              'migmag_tbt_vocabulary',
            ],
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Order of the source lookup migrations determines the returned value" => [
        'Value to transform' => 'vocabulary 2',
        'Row' => [
          'source' => ['source_entity_type_prop' => 'something_else'],
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => 'vocabulary_2_1',
        'Config' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => [
              'migmag_tbt_vocabulary_derived:id_collision',
              'migmag_tbt_vocabulary',
            ],
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived:id_collision',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' with lookup addressing specific migration" => [
        'Value to transform' => 'vocabulary 2',
        'Row' => [
          'source' => ['source_entity_type_prop' => 'something_else'],
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => 'vocabulary_2_1',
        'Config' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => 'migmag_tbt_vocabulary',
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_vocabulary_derived',
          'migmag_tbt_vocabulary',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' without lookup conf" => [
        'Value to transform' => 'vocabulary 2',
        'Row' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'Expected' => 'vocabulary_2_1',
        'Config' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => 'migmag_tbt_vocabulary',
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'Migrations to execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],
    ];
  }

}
