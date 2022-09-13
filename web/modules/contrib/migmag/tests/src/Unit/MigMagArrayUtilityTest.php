<?php

namespace Drupal\Tests\migmag\Unit;

use Drupal\migmag\Utility\MigMagArrayUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests MigMagArrayUtility.
 *
 * @coversDefaultClass \Drupal\migmag\Utility\MigMagArrayUtility
 *
 * @group migmag
 */
class MigMagArrayUtilityTest extends UnitTestCase {

  /**
   * A dummy migration process pipeline array used for testing.
   *
   * @const array[]
   */
  const TEST_MIGRATION_PROCESS = [
    'first' => 'foo',
    'second' => 'bar',
    'third' => 'baz',
  ];

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::insertInFrontOfKey
   *
   * @dataProvider providerInsertInFront
   */
  public function testInsertInFrontOfKey(array $test_migration_processes, string $next_destination, $new_process_pipeline, bool $overwrite, array $expected_processes, string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::insertInFrontOfKey(
      $test_migration_processes,
      $next_destination,
      'new',
      $new_process_pipeline,
      $overwrite
    );

    $this->assertSame(
      $expected_processes,
      $test_migration_processes
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::insertAfterKey
   *
   * @dataProvider providerInsertAfter
   */
  public function testInsertAfterKey(array $test_migration_processes, string $next_destination, $new_process_pipeline, bool $overwrite, array $expected_processes, string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::insertAfterKey(
      $test_migration_processes,
      $next_destination,
      'new',
      $new_process_pipeline,
      $overwrite
    );

    $this->assertSame(
      $expected_processes,
      $test_migration_processes
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::moveInFrontOfKey
   *
   * @dataProvider providerMoveInFrontOf
   */
  public function testMoveInFrontOf(array $test_array, string $reference_key, string $moved_key, array $expected_array, string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::moveInFrontOfKey(
      $test_array,
      $reference_key,
      $moved_key
    );

    $this->assertSame(
      $expected_array,
      $test_array
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::moveAfterKey
   *
   * @dataProvider providerMoveAfter
   */
  public function testMoveAfter(array $test_array, string $reference_key, string $moved_key, array $expected_array, string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::moveAfterKey(
      $test_array,
      $reference_key,
      $moved_key
    );

    $this->assertSame(
      $expected_array,
      $test_array
    );
  }

  /**
   * Data provider for ::testInsertInFrontOfKey.
   *
   * @return array
   *   The test cases.
   */
  public function providerInsertInFront(): array {
    return [
      'Insert in front of first' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Before' => 'first',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'new' => 'new',
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert in front of second' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Before' => 'second',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'new' => 'new',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert in front of third' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Before' => 'third',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'second' => 'bar',
          'new' => 'new',
          'third' => 'baz',
        ],
      ],

      'Preexisting process in the right pos, no overwrite' => [
        'Test process' => ['new' => 'new'] + self::TEST_MIGRATION_PROCESS,
        'Before' => 'third',
        'New process' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'Overwrite' => FALSE,
        'Expected' => [
          'new' => 'new',
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Preexisting process in the right pos, with overwrite' => [
        'Test process' => ['new' => 'new'] + self::TEST_MIGRATION_PROCESS,
        'Before' => 'third',
        'New process' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'Overwrite' => TRUE,
        'Expected' => [
          'new' => [
            'plugin' => 'get',
            'source' => 'foo',
          ],
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Missing reference point' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Before' => 'missing',
        'New process' => 'baz',
        'Overwrite' => FALSE,
        'Expected' => [],
        'Exception' => "The reference key 'missing' cannot be found in the array.",
      ],
    ];
  }

  /**
   * Data provider for ::testInsertAfterKey.
   *
   * @return array
   *   The test cases.
   */
  public function providerInsertAfter(): array {
    return [
      'Insert after first' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'first',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'new' => 'new',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert after second' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'second',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'second' => 'bar',
          'new' => 'new',
          'third' => 'baz',
        ],
      ],

      'Insert after third' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'third',
        'New process' => 'new',
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => 'new',
        ],
      ],

      'Preexisting process in the right pos, no overwrite' => [
        'Test process' => self::TEST_MIGRATION_PROCESS + ['new' => 'new'],
        'Reference' => 'second',
        'New process' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'Overwrite' => FALSE,
        'Expected' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => 'new',
        ],
      ],

      'Preexisting process in the right pos, with overwrite' => [
        'Test process' => self::TEST_MIGRATION_PROCESS + ['new' => 'new'],
        'Reference' => 'third',
        'New process' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'Overwrite' => TRUE,
        'Expected' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => [
            'plugin' => 'get',
            'source' => 'foo',
          ],
        ],
      ],

      'Missing reference point' => [
        'Test process' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'missing',
        'New process' => 'baz',
        'Overwrite' => FALSE,
        'Expected' => [],
        'Exception' => "The reference key 'missing' cannot be found in the array.",
      ],
    ];
  }

  /**
   * Data provider for ::testMoveAfter.
   *
   * @return array
   *   The test cases.
   */
  public function providerMoveAfter(): array {
    return [
      'Move first after second' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'second',
        'Moved key' => 'first',
        'Expected array' => [
          'second' => 'bar',
          'first' => 'foo',
          'third' => 'baz',
        ],
      ],

      'Move first after third' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'third',
        'Moved key' => 'first',
        'Expected array' => [
          'second' => 'bar',
          'third' => 'baz',
          'first' => 'foo',
        ],
      ],

      'Third after first' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'first',
        'Moved key' => 'third',
        'Expected array' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testMoveInFrontOf.
   *
   * @return array
   *   The test cases.
   */
  public function providerMoveInFrontOf(): array {
    return [
      'Move third in front of first' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'first',
        'Moved key' => 'third',
        'Expected array' => [
          'third' => 'baz',
          'first' => 'foo',
          'second' => 'bar',
        ],
      ],

      'Move third in front of second' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'second',
        'Moved key' => 'third',
        'Expected array' => [
          'first' => 'foo',
          'third' => 'baz',
          'second' => 'bar',
        ],
      ],

      'First in fron of third' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'third',
        'Moved key' => 'first',
        'Expected array' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],
    ];
  }

  /**
   * @covers ::addSuffixToArrayValues
   *
   * @dataProvider providerAddSuffixToArrayValues
   */
  public function testAddSuffixToArrayValues(array $dependencies, array $dependency_ids_to_process, string $derivative_suffix, array $expected_dependencies): void {
    MigMagArrayUtility::addSuffixToArrayValues($dependencies, $dependency_ids_to_process, $derivative_suffix);
    $this->assertSame($expected_dependencies, $dependencies);
  }

  /**
   * Data provider for ::testAddSuffixToMigrationDependencies.
   *
   * @return array
   *   The test cases.
   */
  public function providerAddSuffixToArrayValues(): array {
    return [
      'Single matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['bar'],
        'Suffix' => ':sub:bar',
        'Expected' => [
          'foo',
          'bar:sub:bar',
          'baz',
        ],
      ],

      'No matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['missing'],
        'Suffix' => ':sub:bar',
        'Expected' => [
          'foo',
          'bar',
          'baz',
        ],
      ],

      'Multiple matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['foo', 'bar', 'missing'],
        'Suffix' => ':sub:bar',
        'Expected' => [
          'foo:sub:bar',
          'bar:sub:bar',
          'baz',
        ],
      ],

      'Tricky' => [
        'Original' => [
          'foo',
          'foobar',
          'baz',
        ],
        'Deps to update' => ['foobar'],
        'Suffix' => 'bar',
        'Expected' => [
          'foo',
          'foobarbar',
          'baz',
        ],
      ],
    ];
  }

}
