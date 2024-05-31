<?php

namespace Drupal\Tests\migmag_process\Kernel\Plugin;

use Drupal\migmag_process\Plugin\migrate\process\MigMagTry;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests the MigMagTry migrate process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagTry
 *
 * @group migmag_process
 */
class MigMagTryTest extends MigrateTestBase {

  /**
   * The destination property used for testing.
   *
   * @const string
   */
  const TEST_DESTINATION_PROPERTY = 'dest_prop';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_link_content',
    'migmag',
    'migmag_process',
    'migrate_events_test',
    'system',
  ];

  /**
   * The test migration's configuration.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'migmag_try_test',
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [['id' => 2]],
      'ids' => ['id' => ['type' => 'integer']],
    ],
    'process' => ['value' => 'id'],
    'destination' => ['plugin' => 'dummy'],
  ];

  /**
   * Migration plugin instance used for the actual test.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Tests transform.
   *
   * @covers \Drupal\migmag_process\Plugin\migrate\process\MigMagTry::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(array $row_data = [], array $plugin_config = [], $expected_transformed = NULL, $expected_exception = NULL): void {
    $this->migration = new Migration(
      $this->migrationConfiguration,
      $this->migrationConfiguration['id'],
      [],
      $this->container->get('plugin.manager.migration'),
      $this->container->get('plugin.manager.migrate.source'),
      $this->container->get('plugin.manager.migrate.process'),
      $this->container->get('plugin.manager.migrate.destination'),
      $this->container->get('plugin.manager.migrate.id_map')
    );
    $executable = new MigrateExecutable($this->migration);
    $row = new Row(
      ['id' => 2] + $row_data,
      ['id' => ['type' => 'integer']]
    );
    $executable_ref = new \ReflectionObject($executable);
    $source_id_values = $executable_ref->getProperty('sourceIdValues');
    $source_id_values->setAccessible(TRUE);
    $source_id_values->setValue($executable, $row->getSourceIdValues());

    $plugin = new MigMagTry($plugin_config, 'migmag_try', [], $this->migration);

    if ($expected_exception) {
      $this->expectException($expected_exception);
    }

    $actual_transformed = $plugin->transform(
      NULL,
      $executable,
      $row,
      self::TEST_DESTINATION_PROPERTY
    );

    $this->assertEquals($expected_transformed, $actual_transformed);
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransform(): array {
    return [
      'Existing property - no exception' => [
        'row' => ['foo' => 'foo_val'],
        'config' => [
          'process' => 'foo',
        ],
        'expected' => 'foo_val',
      ],
      'Missing property - no exception' => [
        'row' => [],
        'config' => [
          'process' => 'foo',
        ],
        'expected' => NULL,
      ],
      'Uncatched bad method call exception' => [
        'row' => [],
        'config' => [
          'catch' => [],
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'foo',
          ],
        ],
        'expected' => NULL,
        'exception' => \BadMethodCallException::class,
      ],
      'Catched bad method call exception' => [
        'row' => [],
        'config' => [
          'catch' => [
            'BadMethodCallException' => 'bad method call',
          ],
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'bar',
          ],
        ],
        'expected' => 'bad method call',
      ],
      'Skip row catched with Exception' => [
        'row' => [],
        'config' => [
          'catch' => [
            'Exception' => 'exception',
          ],
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'bar',
            'method' => 'row',
          ],
        ],
        'expected' => 'exception',
      ],
      'Skip row catched with MigrateSkipRowException' => [
        'row' => [],
        'config' => [
          'catch' => [
            'Drupal\migrate\MigrateSkipRowException' => 'migrate skip row exception',
            'Exception' => 'exception',
          ],
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'bar',
            'method' => 'row',
          ],
        ],
        'expected' => 'migrate skip row exception',
      ],
      'Showing that catch key order does not matters' => [
        'row' => [],
        'config' => [
          'catch' => [
            'Exception' => 'exception',
            'Drupal\migrate\MigrateSkipRowException' => 'migrate skip row exception',
          ],
          'process' => [
            [
              'plugin' => 'get',
              'source' => 'bar',
            ],
            [
              'plugin' => 'skip_on_empty',
              'method' => 'row',
            ],
          ],
        ],
        'expected' => 'migrate skip row exception',
      ],
    ];
  }

  /**
   * Tests migration message saving.
   *
   * @dataProvider providerMessageSaving
   */
  public function testMessageSaving(array $plugin_config, string $expected_message = NULL, int $expected_level = NULL, string $failing_process_plugin = NULL) {
    $this->testTransform(
      [],
      $plugin_config
    );

    $id_map = $this->migration->getIdMap();
    $messages_trans = $id_map->getMessages(['id' => 2]);
    $message = (array) (iterator_to_array($messages_trans, FALSE)[0] ?? NULL);

    if ($expected_message === NULL) {
      $this->assertEmpty($message);
      return;
    }

    $core_major_minor = implode(
      '.',
      [
        explode('.', \Drupal::VERSION)[0],
        explode('.', \Drupal::VERSION)[1],
      ]
    );
    $message_parts[] = implode(':', [
      $this->migration->getPluginId(),
      self::TEST_DESTINATION_PROPERTY,
      '',
    ]);
    if ($failing_process_plugin && version_compare($core_major_minor, '9.4', 'ge')) {
      $message_parts[] = "$failing_process_plugin:";
    }

    $this->assertEquals(
      [
        'message' => implode(' ', array_merge(
          $message_parts,
          [$expected_message]
        )),
        'level' => $expected_level,
      ],
      [
        'message' => $message['message'],
        'level' => $message['level'],
      ]
    );
  }

  /**
   * Data provider for ::testMessageSaving.
   *
   * @return array
   *   The test cases.
   */
  public function providerMessageSaving(): array {
    return [
      'Skip row with message' => [
        'config' => [
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'foo',
            'message' => 'bar',
            'method' => 'row',
          ],
        ],
        'expected message' => 'bar',
        'expected level' => Migration::MESSAGE_INFORMATIONAL,
      ],
      'Skip row without message' => [
        'config' => [
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'foo',
            'method' => 'row',
          ],
        ],
      ],
      'Skip row with suppressed message' => [
        'config' => [
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'foo',
            'message' => 'bar',
            'method' => 'row',
          ],
          'saveMessage' => FALSE,
        ],
      ],
      'MigrateException' => [
        'config' => [
          'process' => [
            'plugin' => 'link_uri',
            'source' => 'id',
          ],
        ],
        'expected message' => 'The path "internal:/2" failed validation.',
        'expected level' => MigrationInterface::MESSAGE_ERROR,
        'failing process plugin' => 'link_uri',
      ],
      'MigrateException with suppressed message' => [
        'config' => [
          'process' => [
            'plugin' => 'link_uri',
            'source' => 'id',
          ],
          'saveMessage' => FALSE,
        ],
      ],
      'BadMethodCallException' => [
        'config' => [
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'bar',
          ],
        ],
      ],
      'BadMethodCallException with suppressed message' => [
        'config' => [
          'process' => [
            'plugin' => 'skip_on_empty',
            'source' => 'bar',
          ],
          'saveMessage' => FALSE,
        ],
      ],
    ];
  }

}
