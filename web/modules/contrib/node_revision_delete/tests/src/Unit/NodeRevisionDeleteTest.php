<?php

namespace Drupal\Tests\node_revision_delete\Unit;

use Drupal\node_revision_delete\NodeRevisionDeleteBatch;
use Drupal\Tests\UnitTestCase;
use Drupal\node_revision_delete\NodeRevisionDelete;
use Drupal\Tests\node_revision_delete\Traits\NodeRevisionDeleteTestTrait;

/**
 * Tests the NodeRevisionDelete class methods.
 *
 * @group node_revision_delete
 * @coversDefaultClass \Drupal\node_revision_delete\NodeRevisionDelete
 */
class NodeRevisionDeleteTest extends UnitTestCase {

  use NodeRevisionDeleteTestTrait;

  /**
   * A connection instance.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * A config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * An entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * A language manager instance.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The NodeRevisionDelete Object.
   *
   * @var \Drupal\node_revision_delete\NodeRevisionDelete
   */
  protected $nodeRevisionDelete;

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configFile;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Setting the config file.
    $this->configFile = 'node_revision_delete.settings';

    // Connection mock.
    $this->connection = $this->createMock('Drupal\Core\Database\Connection');
    // Config factory mock.
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    // Entity Type Manager mock.
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    // Language Manager mock.
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');

    // Creating the object.
    $this->nodeRevisionDelete = new NodeRevisionDelete(
      $this->configFactory,
      $this->getStringTranslationStub(),
      $this->connection,
      $this->entityTypeManager,
      $this->languageManager
    );
  }

  /**
   * Tests the getTimeString() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param array $config_name_time
   *   The configured time name.
   * @param string $config_name
   *   The config name.
   * @param int $number
   *   The number for the $config_name parameter configuration.
   *
   * @covers ::getTimeString
   * @dataProvider providerGetTimeString
   */
  public function testGetTimeString($expected, array $config_name_time, $config_name, $number) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('node_revision_delete_' . $config_name . '_time')
      ->willReturn($config_name_time);

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with($this->configFile)
      ->willReturn($config);

    // Asserting the values.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeString($config_name, $number));
  }

  /**
   * Data provider for testGetTimeString.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeString().
   *   - 'config_name_time' - The configured time name.
   *   - 'config_name' - The config name.
   *   - 'max_number' - The number for the $config_name parameter configuration.
   *
   * @see testGetTimeString()
   */
  public function providerGetTimeString() {

    $expected = [
      '5 days',
      '2 days',
      '1 day',
      '10 weeks',
      '20 weeks',
      '1 week',
      '12 months',
      '24 months',
      '1 month',
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 5, '@time' => 'days']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 2, '@time' => 'days']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'day']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 10, '@time' => 'weeks']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 20, '@time' => 'weeks']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'week']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 12, '@time' => 'months']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 24, '@time' => 'months']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'month']),
    ];

    $days = ['time' => 'days'];
    $weeks = ['time' => 'weeks'];
    $months = ['time' => 'months'];

    // Test for minimum_age_to_delete.
    $tests['days 1'] = [$expected[0], $days, 'minimum_age_to_delete', 5];
    $tests['days 2'] = [$expected[1], $days, 'minimum_age_to_delete', 2];
    $tests['days 3'] = [$expected[2], $days, 'minimum_age_to_delete', 1];
    $tests['weeks 1'] = [$expected[3], $weeks, 'minimum_age_to_delete', 10];
    $tests['weeks 2'] = [$expected[4], $weeks, 'minimum_age_to_delete', 20];
    $tests['weeks 3'] = [$expected[5], $weeks, 'minimum_age_to_delete', 1];
    $tests['months 1'] = [$expected[6], $months, 'minimum_age_to_delete', 12];
    $tests['months 2'] = [$expected[7], $months, 'minimum_age_to_delete', 24];
    $tests['months 3'] = [$expected[8], $months, 'minimum_age_to_delete', 1];
    // Test for when_to_delete.
    $tests['days 4'] = [$expected[9], $days, 'when_to_delete', 5];
    $tests['days 5'] = [$expected[10], $days, 'when_to_delete', 2];
    $tests['days 6'] = [$expected[11], $days, 'when_to_delete', 1];
    $tests['weeks 4'] = [$expected[12], $weeks, 'when_to_delete', 10];
    $tests['weeks 5'] = [$expected[13], $weeks, 'when_to_delete', 20];
    $tests['weeks 6'] = [$expected[14], $weeks, 'when_to_delete', 1];
    $tests['months 4'] = [$expected[15], $months, 'when_to_delete', 12];
    $tests['months 5'] = [$expected[16], $months, 'when_to_delete', 24];
    $tests['months 6'] = [$expected[17], $months, 'when_to_delete', 1];

    return $tests;
  }

  /**
   * Tests the getTimeValues() method.
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param string $index
   *   The index to retrieve.
   *
   * @covers ::getTimeValues
   * @dataProvider providerGetTimeValues
   */
  public function testGetTimeValues($expected, $index) {
    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeValues($index));
  }

  /**
   * Data provider for testGetTimeNumberString().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeValues().
   *   - 'index' - The number.
   *
   * @see testGetTimeValues()
   */
  public function providerGetTimeValues() {
    $all_values = [
      '-1'       => 'Never',
      '0'        => 'Every time cron runs',
      '3600'     => 'Every hour',
      '86400'    => 'Everyday',
      '604800'   => 'Every week',
      '864000'   => 'Every 10 days',
      '1296000'  => 'Every 15 days',
      '2592000'  => 'Every month',
      '7776000'  => 'Every 3 months',
      '15552000' => 'Every 6 months',
      '31536000' => 'Every year',
      '63072000' => 'Every 2 years',
    ];

    $tests[] = [$all_values, NULL];
    $tests[] = [$all_values[-1], -1];
    $tests[] = [$all_values[0], 0];
    $tests[] = [$all_values[3600], 3600];
    $tests[] = [$all_values[86400], 86400];
    $tests[] = [$all_values[604800], 604800];
    $tests[] = [$all_values[864000], 864000];
    $tests[] = [$all_values[1296000], 1296000];
    $tests[] = [$all_values[2592000], 2592000];
    $tests[] = [$all_values[7776000], 7776000];
    $tests[] = [$all_values[15552000], 15552000];
    $tests[] = [$all_values[31536000], 31536000];
    $tests[] = [$all_values[63072000], 63072000];

    return $tests;
  }

  /**
   * Tests the getTimeNumberString() method.
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param string $number
   *   The number.
   * @param string $time
   *   The time option (days, weeks or months).
   *
   * @covers ::getTimeNumberString
   * @dataProvider providerGetTimeNumberString
   */
  public function testGetTimeNumberString($expected, $number, $time) {
    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeNumberString($number, $time));
  }

  /**
   * Data provider for testGetTimeNumberString().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeNumberString().
   *   - 'number' - The number.
   *   - 'time' - The time option (days, weeks or months).
   *
   * @see testGetTimeNumberString()
   */
  public function providerGetTimeNumberString() {
    // Days.
    $tests['day singular'] = ['day', 1, 'days'];
    $tests['day plural 1'] = ['days', 2, 'days'];
    $tests['day plural 2'] = ['days', 10, 'days'];
    // Weeks.
    $tests['week singular'] = ['week', 1, 'weeks'];
    $tests['week plural 1'] = ['weeks', 2, 'weeks'];
    $tests['week plural 2'] = ['weeks', 10, 'weeks'];
    // Months.
    $tests['month singular'] = ['month', 1, 'months'];
    $tests['month plural 1'] = ['months', 2, 'months'];
    $tests['month plural 2'] = ['months', 10, 'months'];

    return $tests;
  }

  /**
   * Tests the getRevisionDeletionBatch() method.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $revisions
   *   The revisions array.
   * @param bool $dry_run
   *   The dry run option.
   *
   * @covers ::getRevisionDeletionBatch
   * @dataProvider providerGetRevisionDeletionBatch
   */
  public function testGetRevisionDeletionBatch(array $expected, array $revisions, $dry_run) {
    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getRevisionDeletionBatch($revisions, $dry_run));
  }

  /**
   * Data provider for testGetRevisionDeletionBatch().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from ::getRevisionDeletionBatch().
   *   - 'revisions' - An array of revisions.
   *   - 'dry_run' - An option to know if we should delete or not the revisions.
   *
   * @see testGetRevisionDeletionBatch()
   */
  public function providerGetRevisionDeletionBatch() {
    // Sets of revisions.
    $revision_sets = [
      [],
      [12],
      [32, 4],
      [45, 23, 72],
      [76, 97, 34, 53],
    ];
    // Set for the dry run option.
    $dry_run_set = [
      TRUE,
      TRUE,
      TRUE,
      FALSE,
      FALSE,
    ];

    // The batch.
    $batch_template = [
      'title' => $this->getStringTranslationStub()->translate('Deleting revisions'),
      'init_message' => $this->getStringTranslationStub()->translate('Starting to delete revisions.'),
      'progress_message' => $this->getStringTranslationStub()->translate('Deleted @current out of @total (@percentage%). Estimated time: @estimate.'),
      'error_message' => $this->getStringTranslationStub()->translate('Error deleting revisions.'),
      'operations' => [],
      'finished' => [NodeRevisionDeleteBatch::class, 'finish'],
      'file' => NULL,
      'library' => [],
      'url_options' => [],
      'progressive' => TRUE,
    ];

    $expected = [];

    // Creating the expected arrays.
    foreach ($revision_sets as $set => $revisions) {
      $expected[$set] = $batch_template;

      foreach ($revisions as $revision) {
        $expected[$set]['operations'][] = [
          [NodeRevisionDeleteBatch::class, 'deleteRevision'],
          [$revision, $dry_run_set[$set]],
        ];
      }
    }

    $tests[] = [$expected[0], $revision_sets[0], $dry_run_set[0]];
    $tests[] = [$expected[1], $revision_sets[1], $dry_run_set[1]];
    $tests[] = [$expected[2], $revision_sets[2], $dry_run_set[2]];
    $tests[] = [$expected[3], $revision_sets[3], $dry_run_set[3]];
    $tests[] = [$expected[4], $revision_sets[4], $dry_run_set[4]];

    return $tests;
  }

  /**
   * Tests the getRelativeTime() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param array $time
   *   The configured time name.
   * @param string $config_name
   *   The config name.
   * @param int $number
   *   The number for the $config_name parameter configuration.
   *
   * @covers ::getRelativeTime
   * @dataProvider providerGetRelativeTime
   */
  public function testGetRelativeTime($expected, array $time, $config_name, $number) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('node_revision_delete_' . $config_name . '_time')
      ->willReturn($time);

    // Mocking get method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with($this->configFile)
      ->willReturn($config);

    // Asserting the values.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getRelativeTime($config_name, $number));
  }

  /**
   * Data provider for testGetRelativeTime.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getRelativeTime().
   *   - 'time' - The configured time.
   *   - 'config_name' - The config name.
   *   - 'number' - The number for the $config_name parameter configuration.
   *
   * @see testGetRelativeTime()
   */
  public function providerGetRelativeTime() {

    $expected = [
      strtotime('-5 days'),
      strtotime('-2 days'),
      strtotime('-1 day'),
      strtotime('-10 weeks'),
      strtotime('-20 weeks'),
      strtotime('-1 week'),
      strtotime('-12 months'),
      strtotime('-24 months'),
      strtotime('-1 month'),
    ];

    $days = ['time' => 'days'];
    $weeks = ['time' => 'weeks'];
    $months = ['time' => 'months'];

    // Test for minimum_age_to_delete.
    $tests['days 1'] = [$expected[0], $days, 'minimum_age_to_delete', 5];
    $tests['days 2'] = [$expected[1], $days, 'minimum_age_to_delete', 2];
    $tests['days 3'] = [$expected[2], $days, 'minimum_age_to_delete', 1];
    $tests['weeks 1'] = [$expected[3], $weeks, 'minimum_age_to_delete', 10];
    $tests['weeks 2'] = [$expected[4], $weeks, 'minimum_age_to_delete', 20];
    $tests['weeks 3'] = [$expected[5], $weeks, 'minimum_age_to_delete', 1];
    $tests['months 1'] = [$expected[6], $months, 'minimum_age_to_delete', 12];
    $tests['months 2'] = [$expected[7], $months, 'minimum_age_to_delete', 24];
    $tests['months 3'] = [$expected[8], $months, 'minimum_age_to_delete', 1];
    // Test for when_to_delete.
    $tests['days 4'] = [$expected[0], $days, 'when_to_delete', 5];
    $tests['days 5'] = [$expected[1], $days, 'when_to_delete', 2];
    $tests['days 6'] = [$expected[2], $days, 'when_to_delete', 1];
    $tests['weeks 4'] = [$expected[3], $weeks, 'when_to_delete', 10];
    $tests['weeks 5'] = [$expected[4], $weeks, 'when_to_delete', 20];
    $tests['weeks 6'] = [$expected[5], $weeks, 'when_to_delete', 1];
    $tests['months 4'] = [$expected[6], $months, 'when_to_delete', 12];
    $tests['months 5'] = [$expected[7], $months, 'when_to_delete', 24];
    $tests['months 6'] = [$expected[8], $months, 'when_to_delete', 1];

    return $tests;
  }

  /**
   * Tests the getConfiguredContentTypes() method.
   *
   * @param array $expected
   *   The expected result from calling the function.
   * @param array $third_party_settings
   *   The content type third party settings.
   * @param array $content_types_list
   *   A list with node types objects.
   *
   * @covers ::getConfiguredContentTypes
   * @dataProvider providerGetConfiguredContentTypes
   */
  public function testGetConfiguredContentTypes(array $expected, array $third_party_settings, array $content_types_list) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('third_party_settings')
      ->willReturnOnConsecutiveCalls(...$third_party_settings);

    $cant = count($content_types_list);
    $map_node_type = [];

    for ($i = 0; $i < $cant; $i++) {
      $map_node_type[] = [
        'node.type.' . $content_types_list[$i]->id(),
        $config,
      ];
    }

    // Mocking get method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map_node_type));

    // EntityStorage mock.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // loadMultiple mock.
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($content_types_list);
    // Mocking getStorage method.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node_type')
      ->willReturn($entity_storage);

    // Testing the method.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getConfiguredContentTypes());
  }

  /**
   * Data provider for testGetConfiguredContentTypes().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return.
   *   - 'third_party_settings' - The content type third party settings.
   *   - 'content_types_list' - A list with content types objects.
   *
   * @see testGetConfiguredContentTypes()
   */
  public function providerGetConfiguredContentTypes() {

    $third_party_settings = [
      [
        ['node_revision_delete' => [0]],
        ['www' => [1]],
        ['node_revision_delete' => [2]],
      ],
      [
        ['xxx' => [0]],
        ['node_revision_delete' => [1]],
        ['yyy' => [2]],
        ['node_revision_delete' => [3]],
      ],
      [
        ['xxx' => [1]],
        ['yyy' => [3]],
      ],
      [
        ['node_revision_delete' => [1]],
        ['node_revision_delete' => [2]],
        ['node_revision_delete' => [3]],
      ],
      [
        ['node_revision_delete' => [1]],
      ],
    ];

    $content_types_list = $this->getConfiguredContentTypes();

    $expected = [
      [
        $content_types_list[0]['0'],
        $content_types_list[0]['2'],
      ],
      [
        $content_types_list[1]['1'],
        $content_types_list[1]['3'],
      ],
      [],
      [
        $content_types_list[3]['0'],
        $content_types_list[3]['1'],
        $content_types_list[3]['2'],
      ],
      [
        $content_types_list[4]['0'],
      ],
    ];

    $tests = [];
    $tests[] = [$expected[0], $third_party_settings[0], $content_types_list[0]];
    $tests[] = [$expected[1], $third_party_settings[1], $content_types_list[1]];
    $tests[] = [$expected[2], $third_party_settings[2], $content_types_list[2]];
    $tests[] = [$expected[3], $third_party_settings[3], $content_types_list[3]];
    $tests[] = [$expected[4], $third_party_settings[4], $content_types_list[4]];
    return $tests;
  }

  /**
   * Returns a content type list.
   *
   * @return array
   *   An array of node type objects.
   */
  private function getConfiguredContentTypes() {

    $content_types = [
      [
        'article',
        'page',
        'test',
      ],
      [
        'article',
        'blog',
        'house',
        'page',
      ],
      [
        'blog',
        'house',
      ],
      [
        'article',
        'blog',
        'page',
      ],
      [
        'blog',
      ],
    ];

    // Getting the number of tests.
    $number_of_tests = count($content_types);
    $content_types_list = [];
    // Creating the array of objects.
    for ($i = 0; $i < $number_of_tests; $i++) {
      $j = 0;

      // Creating the array of objects.
      foreach ($content_types[$i] as $id) {
        // EntityInterface mock.
        $content_types_list[$i][$j] = $this->createMock('Drupal\Core\Entity\EntityInterface');
        // Mocking id method.
        $content_types_list[$i][$j]->expects($this->any())
          ->method('id')
          ->willReturn($id);

        $j++;
      }
    }
    return $content_types_list;
  }

  /**
   * Mock some NodeRevisionDelete class functions.
   *
   * @param array $content_types_list
   *   A list with content types objects.
   *
   * @return \Drupal\node_revision_delete\NodeRevisionDelete|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked class.
   */
  private function getNodeRevisionDeleteMock(array $content_types_list) {
    // Mock NodeRevisionDelete.
    /** @var \Drupal\node_revision_delete\NodeRevisionDelete|\PHPUnit_Framework_MockObject_MockObject $controller */
    $controller = $this->getMockBuilder('Drupal\node_revision_delete\NodeRevisionDelete')
      ->setConstructorArgs([
        $this->configFactory,
        $this->getStringTranslationStub(),
        $this->connection,
        $this->entityTypeManager,
        $this->languageManager,
      ])
      // Specify that we'll also mock other methods.
      ->setMethods(['getConfiguredContentTypes'])
      ->getMock();

    // Mock getContentTypesList().
    $controller->expects($this->any())
      ->method('getConfiguredContentTypes')
      ->willReturn($content_types_list);

    return $controller;
  }

  /**
   * Tests the updateTimeMaxNumberConfig() method.
   *
   * @param array $third_party_settings
   *   The content type third party settings.
   * @param array $content_types_list
   *   A list with node types objects.
   * @param int $max_number
   *   The maximum number for $config_name parameter.
   *
   * @covers ::updateTimeMaxNumberConfig
   * @dataProvider providerUpdateTimeMaxNumberConfig
   */
  public function testUpdateTimeMaxNumberConfig(array $third_party_settings, array $content_types_list, $max_number) {
    // Mock NodeRevisionDelete.
    $controller = $this->getNodeRevisionDeleteMock($content_types_list);

    // Config mock.
    $config = $this->createMock('Drupal\Core\Config\Config');
    // Config::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('third_party_settings')
      ->willReturnOnConsecutiveCalls(...$third_party_settings);

    $config->expects($this->any())
      ->method('set')
      ->with('third_party_settings', $this->anything())
      ->willReturnSelf();

    $config->expects($this->any())
      ->method('save');

    $cant = count($content_types_list);
    $map_node_type = [];
    for ($i = 0; $i < $cant; $i++) {
      $map_node_type[] = [
        'node.type.' . $content_types_list[$i]->id(),
        $config,
      ];
    }

    // Mocking get method.
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->will($this->returnValueMap($map_node_type));

    // Testing the method.
    $this->assertNull($controller->updateTimeMaxNumberConfig('minimum_age_to_delete', $max_number));
    $this->assertNull($controller->updateTimeMaxNumberConfig('when_to_delete', $max_number));
  }

  /**
   * Data provider for testUpdateTimeMaxNumberConfig().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'third_party_settings' - The content type third party settings.
   *   - 'content_types_list' - A list with content types objects.
   *
   * @see testUpdateTimeMaxNumberConfig()
   */
  public function providerUpdateTimeMaxNumberConfig() {
    $third_party_settings = [
      // 3 elements.
      [
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 5,
              'when_to_delete' => 3,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 10,
              'when_to_delete' => 12,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 5,
              'when_to_delete' => 5,
            ],
        ],
      ],
      // 4 elements.
      [
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 5,
              'when_to_delete' => 3,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 10,
              'when_to_delete' => 12,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 8,
              'when_to_delete' => 2,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 3,
              'when_to_delete' => 2,
            ],
        ],
      ],
      // 2 elements.
      [
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 10,
              'when_to_delete' => 12,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 8,
              'when_to_delete' => 2,
            ],
        ],
      ],
      // 3 elements.
      [
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 15,
              'when_to_delete' => 13,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 1,
              'when_to_delete' => 1,
            ],
        ],
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 15,
              'when_to_delete' => 15,
            ],
        ],
      ],
      // 1 element.
      [
        [
          'node_revision_delete' =>
            [
              'minimum_age_to_delete' => 5,
              'when_to_delete' => 3,
            ],
        ],
      ],
    ];

    $content_types_list = $this->getConfiguredContentTypes();

    $tests = [];
    $tests[] = [$third_party_settings[0], $content_types_list[0], 2];
    $tests[] = [$third_party_settings[1], $content_types_list[1], 4];
    $tests[] = [$third_party_settings[2], $content_types_list[2], 8];
    $tests[] = [$third_party_settings[3], $content_types_list[3], 14];
    $tests[] = [$third_party_settings[4], $content_types_list[4], 2];
    return $tests;
  }

  /**
   * Tests the getContentTypeConfig() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param array $third_party_settings
   *   The content type third party settings.
   * @param string $content_type
   *   Content type machine name.
   *
   * @covers ::getContentTypeConfig
   * @dataProvider providerGetContentTypeConfig
   */
  public function testGetContentTypeConfig($expected, array $third_party_settings, $content_type) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('third_party_settings')
      ->willReturn($third_party_settings);

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('node.type.' . $content_type)
      ->willReturn($config);

    // Asserting the values.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getContentTypeConfig($content_type));
  }

  /**
   * Data provider for testGetContentTypeConfig.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeString().
   *   - 'third_party_settings' - The content type third party settings.
   *   - 'content_type' - Content type machine name.
   *
   * @see testGetTimeString()
   */
  public function providerGetContentTypeConfig() {
    $content_types = [
      'article',
      'page',
      'test',
      'car',
      'house',
    ];

    $third_party_settings = [
      [
        'node_revision_delete' => [0],
      ],
      [
        'xxx' => [0],
      ],
      [
        'xxx' => [1],
      ],
      [
        'node_revision_delete' => [1],
      ],
      [
        'node_revision_delete' => [2],
      ],
    ];

    $expected = [
      [0],
      [],
      [],
      [1],
      [2],
    ];

    $tests = [];
    $tests[] = [$expected[0], $third_party_settings[0], $content_types[0]];
    $tests[] = [$expected[1], $third_party_settings[1], $content_types[1]];
    $tests[] = [$expected[2], $third_party_settings[2], $content_types[2]];
    $tests[] = [$expected[3], $third_party_settings[3], $content_types[3]];
    $tests[] = [$expected[4], $third_party_settings[4], $content_types[4]];

    return $tests;
  }

}
