<?php

namespace Drupal\Tests\media_migration\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\media_migration\MediaWysiwygPluginBase;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Bean Media WYSIWYG plugin.
 *
 * @todo Test the conditions in MediaWysiwygPluginBase::appendProcessor().
 *
 * @coversDefaultClass \Drupal\media_migration\MediaWysiwygPluginBase
 * @group media_migration
 */
class MediaWysiwygPluginBaseTest extends UnitTestCase {

  /**
   * Tests Media WYSIWYG plugin construction.
   *
   * @covers ::__construct
   * @dataProvider providerTestPluginConstruct
   */
  public function testPluginConstruct(array $config, array $plugin_definition, $expected_exception_regex): void {
    if ($expected_exception_regex) {
      $this->expectException(PluginException::class);
      if (is_callable([$this, 'expectExceptionMessageMatches'])) {
        $this->expectExceptionMessageMatches($expected_exception_regex);
      }
      else {
        $this->expectExceptionMessageRegExp($expected_exception_regex);
      }
    }

    $plugin = $this->getMockBuilder(MediaWysiwygPluginBase::class)
      ->setConstructorArgs([
        $config,
        'test_plugin_id',
        $plugin_definition,
      ])
      ->setMethods(NULL)
      ->getMock();

    $this->assertInstanceOf(MediaWysiwygPluginBase::class, $plugin);
  }

  /**
   * Tests MediaWysiwygPluginBase::process.
   *
   * @covers ::process
   * @dataProvider providerTestProcess
   */
  public function testProcess(array $field_row_values, array $additional_migrations, array $expected_migrations): void {
    $row = new Row($field_row_values, array_combine(array_keys($field_row_values), array_keys($field_row_values)));

    $plugin = $this->getMockBuilder(MediaWysiwygPluginBase::class)
      ->setConstructorArgs([
        [],
        'test_plugin_id',
        ['entity_type_map' => ['source_entity_type' => 'dest_entity_type']],
      ])
      ->setMethods(NULL)
      ->getMock();

    $migrations = static::UNRELATED_MIGRATIONS + $additional_migrations;
    $expected_migrations = static::UNRELATED_MIGRATIONS + $expected_migrations;

    $actual_processed_migrations = $plugin->process($migrations, $row);

    $this->assertEquals(
      $expected_migrations,
      $actual_processed_migrations
    );
  }

  /**
   * Data provider for ::testPluginConstruct.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestPluginConstruct(): array {
    return [
      'Explicit source config, multiple map item' => [
        'Config' => [
          'source_entity_type_id' => 'source_id_2',
        ],
        'Definition' => [
          'entity_type_map' => [
            'source_id' => 'dest_id',
            'source_id_2' => 'dest_id',
            'source_id_3' => 'dest_id_2',
          ],
        ],
        'Exception' => NULL,
      ],
      'Explicit but invalid config, multiple map item' => [
        'Config' => [
          'source_entity_type_id' => 'unmapped_source_id',
        ],
        'Definition' => [
          'entity_type_map' => [
            'source_id' => 'dest_id',
            'source_id_2' => 'dest_id',
            'source_id_3' => 'dest_id_2',
          ],
        ],
        'Exception' => "/^The MediaWysiwyg plugin instance of class '.*MediaWysiwygPluginBase.*' cannot be instantiated with the following configuration: array(.*)$/s",
      ],
      'Undefined map' => [
        'Config' => [
          'source_entity_type_id' => 'source_id',
        ],
        'Definition' => [],
        'Exception' => "/^The MediaWysiwyg plugin instance of class '.*MediaWysiwygPluginBase.*' cannot be instantiated with the following configuration: array(.*)$/s",
      ],
      'Empty map' => [
        'Config' => [
          'source_entity_type_id' => 'source_id',
        ],
        'Definition' => [
          'entity_type_map' => [],
        ],
        'Exception' => "/^The MediaWysiwyg plugin instance of class '.*MediaWysiwygPluginBase.*' cannot be instantiated with the following configuration: array(.*)$/s",
      ],
      'Empty config, one map item' => [
        'Config' => [],
        'Definition' => [
          'entity_type_map' => [
            'source_id' => 'dest_id',
          ],
        ],
        'Exception' => NULL,
      ],
      'Empty config, multiple map item' => [
        'Config' => [],
        'Definition' => [
          'entity_type_map' => [
            'source_id' => 'dest_id',
            'source_id_2' => 'dest_id',
            'source_id_3' => 'dest_id_2',
          ],
        ],
        'Exception' => "/^The MediaWysiwyg plugin instance of class '.*MediaWysiwygPluginBase.*' cannot be instantiated with the following configuration: array(.*)$/s",
      ],
    ];
  }

  /**
   * Data provider for ::testProcess.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestProcess(): array {
    return [
      'No matching migrations' => [
        'Field row values' => [
          'field_name' => 'field',
          'entity_type' => 'source_entity_type',
        ],
        'Additional migrations' => [],
        'Expected extra migrations' => [],
      ],
      'With a matching migration' => [
        'Field row values' => [
          'field_name' => 'field',
          'entity_type' => 'source_entity_type',
        ],
        'Additional migrations' => [
          'source_entity_type_migration' => [
            'migration_tags' => [
              'Drupal 7',
              'Content',
            ],
            'process' => [
              'field' => 'an_another_field',
              'destination_field' => 'field',
            ],
            'destination' => [
              'plugin' => 'entity:dest_entity_type',
            ],
          ],
        ],
        'Expected extra migrations' => [
          'source_entity_type_migration' => [
            'migration_tags' => [
              'Drupal 7',
              'Content',
            ],
            'process' => [
              'field' => 'an_another_field',
              'destination_field' => [
                [
                  'plugin' => 'get',
                  'source' => 'field',
                ],
                [
                  'plugin' => 'media_wysiwyg_filter',
                ],
              ],
            ],
            'destination' => [
              'plugin' => 'entity:dest_entity_type',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Some unrelated content entity migration definitions.
   *
   * @const array[]
   */
  const UNRELATED_MIGRATIONS = [
    'custom_1' => [
      'migration_tags' => [
        'Drupal 7',
        'Content',
      ],
      'process' => [
        'field' => 'another_field',
        'destination_field' => 'field',
      ],
      'destination' => [
        'plugin' => 'entity:custom_entity',
      ],
    ],
    'custom_2' => [
      'migration_tags' => [
        'Drupal 7',
        'Content',
      ],
      'process' => [
        'field' => 'another_field',
        'destination_field' => 'field',
      ],
      'destination' => [
        'plugin' => 'entity:source_entity_type',
      ],
    ],
  ];

}
