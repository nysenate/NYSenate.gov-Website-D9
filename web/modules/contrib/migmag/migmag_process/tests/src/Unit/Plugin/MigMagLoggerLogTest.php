<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLoggerLog;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Prophecy\Argument;

/**
 * Tests the migmag_logger_log process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagLoggerLog
 *
 * @group migmag_process
 */
class MigMagLoggerLogTest extends MigrateProcessTestCase {

  /**
   * Default row source ID values.
   *
   * @const array
   */
  const DEFAULT_SOURCE_ID_VALUES = [
    'id' => 'source_row_id',
  ];

  /**
   * Storage for the messages logged during testing.
   *
   * @var array
   */
  protected static $log;

  /**
   * A LoggerChannelInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerChannel;

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerChannel = $this->prophesize(LoggerChannelInterface::class);
    $this->loggerChannel->log(Argument::any(), Argument::type('string'), Argument::type('array'))
      ->will(
        function () {
          [
            $level,
            $message,
            $context,
          ] = func_get_args()[0];

          self::$log = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
          ];
        }
      );

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->migration->id()->willReturn('test_migration_id');
  }

  /**
   * Tests the transformation of the provided values.
   *
   * @dataProvider providerTestTransform
   * @covers ::transform
   */
  public function testTransform($value, string $expected_log_message, $expected_log_level = RfcLogLevel::INFO, array $plugin_config = [], $source_row_ids = self::DEFAULT_SOURCE_ID_VALUES, $expected_source_id_values = self::DEFAULT_SOURCE_ID_VALUES) {
    self::$log = [];
    $this->row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue($source_row_ids));
    $this->plugin = new MigMagLoggerLog(
      $plugin_config,
      'migmag_logger_log',
      [],
      $this->migration->reveal(),
      $this->loggerChannel->reveal()
    );

    $result = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');

    // Original value should have been returned without any changes.
    $this->assertEquals($value, $result);
    // Check the arguments sent to the logger.
    $this->assertEquals(
      [
        'message' => $expected_log_message,
        'level' => $expected_log_level,
        'context' => [
          'migration_plugin_id' => 'test_migration_id',
          'source_id_values' => $expected_source_id_values,
        ],
      ],
      self::$log
    );
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestTransform(): array {
    $complex_object = new Get(['config' => 'value'], 'get', []);
    // PHP 8.1+ does this differently.
    $expected_object_message = version_compare(phpversion(), '8.1.0-dev', 'ge')
      ? "Drupal\migrate\Plugin\migrate\process\Get::__set_state(array('pluginId' => 'get', 'pluginDefinition' => array(), 'configuration' => array('config' => 'value'), 'stringTranslation' => NULL, '_serviceIds' => array(), '_entityStorages' => array(), 'messenger' => NULL, 'multiple' => NULL))"
      : "Drupal\migrate\Plugin\migrate\process\Get::__set_state(array('multiple' => NULL, 'pluginId' => 'get', 'pluginDefinition' => array(), 'configuration' => array('config' => 'value'), 'stringTranslation' => NULL, '_serviceIds' => array(), '_entityStorages' => array(), 'messenger' => NULL))";
    $simple_object = (object) [
      'key' => 'value',
      'another key' => 'another value',
    ];

    return [
      'null' => [
        'source' => NULL,
        'expected message' => 'NULL',
      ],
      'boolean false' => [
        'source' => FALSE,
        'expected message' => '(boolean) FALSE',
      ],
      'string false' => [
        'source' => 'FALSE',
        'expected message' => "(string) 'FALSE'",
      ],
      'string' => [
        'source' => 'string',
        'expected message' => "(string) 'string'",
      ],
      'simple object' => [
        'source' => $simple_object,
        'expected message' => "(object) array('key' => 'value', 'another key' => 'another value')",
      ],
      'complex object' => [
        'source' => $complex_object,
        'expected message' => $expected_object_message,
      ],
      'Array' => [
        'source' => [
          'boolean false' => FALSE,
          'boolean true' => TRUE,
          'string' => 'string',
          'null' => NULL,
          'array' => [1, 2],
        ],
        'expected message' => "(array) array(boolean false => (boolean) FALSE, boolean true => (boolean) TRUE, string => (string) 'string', null => NULL, array => (array) array(1, 2))",
      ],
      'Indexed array' => [
        'source' => [
          'string',
          1473635,
          FALSE,
          $simple_object,
        ],
        'expected message' => "(array) array((string) 'string', (integer) 1473635, (boolean) FALSE, (object) array('key' => 'value', 'another key' => 'another value'))",
      ],
      'With message' => [
        'source' => 'value',
        'expected message' => "A log message",
        // RfcLogLevel::INFO.
        'expected level' => 6,
        'config' => [
          'message' => 'A log message',
        ],
      ],
      'With message and args' => [
        'source' => 'value',
        'expected message' => "A log message with args: (string) 'value'",
        'expected level' => 6,
        'config' => [
          'message' => "A log message with args: %s",
        ],
      ],
      'With message, integer level and args' => [
        'source' => [
          'first value',
          'second value',
        ],
        'expected message' => "A log message with args: (string) 'first value'",
        'expected level' => 1,
        'config' => [
          'message' => "A log message with args: %s",
          'log_level' => 1,
        ],
      ],
      'With message, string level and args' => [
        'source' => [
          'first value',
          ['second value'],
        ],
        'expected message' => "A log message with args: (string) 'first value'; (array) array('second value'); missing arg: %s",
        'expected level' => 'warning',
        'config' => [
          'message' => "A log message with args: %s; %s; missing arg: %s",
          'log_level' => 'warning',
        ],
        'row source IDs' => [],
        'expected row source IDs' => 'No source IDs (maybe a subprocess?)',
      ],
    ];
  }

}
