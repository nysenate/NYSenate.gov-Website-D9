<?php

namespace Drupal\Tests\yaml_content\Unit\ContentLoader;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\yaml_content\ContentLoader\ContentLoader;

/**
 * Test the existence checking functionality of the ContentLoader class.
 *
 * @coversDefaultClass \Drupal\yaml_content\ContentLoader\ContentLoader
 * @group yaml_content
 */
class ExistenceCheckingTest extends ContentLoaderTestBase {

  /**
   * An array of pre-built entity definitions for test preparation.
   *
   * @var array
   *
   * @todo Move this into a more dynamic helper class.
   */
  protected $testEntityDefinitions = [
    'node' => [
      'entity_keys' => [
        'id' => 'nid',
        'revision' => 'vid',
        'bundle' => 'type',
        'label' => 'title',
        'langcode' => 'langcode',
        'uuid' => 'uuid',
        'status' => 'status',
        'published' => 'status',
        'uid' => 'uid',
        'default_langcode' => 'default_langcode',
      ],
      'fields' => [
        'nid' => 'nid',
        'vid' => 'vid',
        'uid' => 'uid',
        'type' => 'type',
        'status' => 'status',
        'title' => 'title',
        'body' => 'body',
        'field_existing_field' => 'field_existing_field',
      ],
    ],
  ];

  /**
   * Confirm the default value for existenceCheck().
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoader
   *   The ContentLoader service being tested.
   *
   * @covers ::existenceCheck
   */
  public function testExistenceCheckDefault() {
    $this->assertFalse($this->contentLoader->existenceCheck());

    return $this->contentLoader;
  }

  /**
   * Confirm the existence check value can be enabled.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoader $content_loader
   *   The ContentLoader service being tested.
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoader
   *   The ContentLoader service being tested.
   *
   * @depends testExistenceCheckDefault
   *
   * @covers ::existenceCheck
   * @covers ::setExistenceCheck
   */
  public function testEnableExistenceCheck(ContentLoader $content_loader) {
    $content_loader->setExistenceCheck();

    $this->assertTrue($content_loader->existenceCheck());

    return $content_loader;
  }

  /**
   * Confirm the existence check value can be disabled.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoader $content_loader
   *   The ContentLoader service being tested.
   *
   * @depends testEnableExistenceCheck
   *
   * @covers ::existenceCheck
   * @covers ::setExistenceCheck
   */
  public function testDisableExistenceCheck(ContentLoader $content_loader) {
    $content_loader->setExistenceCheck(FALSE);

    $this->assertFalse($content_loader->existenceCheck());
  }

  /**
   * Tests `buildEntity()` always calls `createEntity()`.
   *
   * @param string $entity_type
   *   The entity type machine name for the content being tested.
   * @param array $test_content
   *   Import content for this test scenario.
   *
   * @dataProvider contentDataProvider
   *
   * @covers ::buildEntity
   * @covers ::createEntity
   */
  public function testBuildEntityCallsCreateEntity($entity_type, array $test_content) {
    $this->markTestSkipped('Enable this test once event dispatching is consolidated.');

    // Stub methods used in the buildEntity() method.
    $this->contentLoader = $this->getContentLoaderMock([
      'dispatchEvent',
      'getEntityTypeDefinition',
      'getContentAttributes',
      'getEntityStorage',
      'getEntityHandler',
      'populateEntityFields',
      'createEntity',
    ]);

    // Confirm `createEntity()` gets called as expected.
    $this->contentLoader->expects($this->once())
      ->method('createEntity')
      ->with(
        $this->equalTo($entity_type),
        $this->equalTo($test_content)
      );

    $this->contentLoader->buildEntity($entity_type, $test_content);
  }

  /**
   * Confirm createEntity doesn't check for existing entities when disabled.
   *
   * @param string $entity_type
   *   The entity type machine name for the content being tested.
   * @param array $test_content
   *   Import content for this test scenario.
   *
   * @dataProvider contentDataProvider
   *
   * @covers ::createEntity
   * @covers ::existenceCheck
   * @covers ::setExistenceCheck
   */
  public function testCreateEntityDoesNotCallEntityExistsWhenDisabled($entity_type, array $test_content) {
    // Stub methods used in the buildEntity() method.
    $this->contentLoader = $this->getContentLoaderMock([
      'getContentAttributes',
      'getEntityStorage',
      'entityExists',
    ]);

    // Ensure existence checking should be disabled.
    $this->contentLoader->setExistenceCheck(FALSE);

    // The `entityExists()` method should never be called.
    $this->contentLoader->expects($this->never())
      ->method('entityExists');

    // Override `getContentAttributes()` to return values based on test data.
    $attributes = $this->getContentAttributes($entity_type, $test_content);
    $this->contentLoader
      ->expects($this->once())
      ->method('getContentAttributes')
      ->willReturn($attributes);

    // Mock the entity storage to confirm `create()` was called.
    $storage_handler_mock = $this->getMockForAbstractClass(EntityStorageInterface::class);
    $storage_handler_mock->expects($this->once())
      ->method('create')
      ->with(
        $attributes['property']
      );

    // Return the mocked storage handler for testing.
    $this->contentLoader->expects($this->once())
      ->method('getEntityStorage')
      ->willReturn($storage_handler_mock);

    $this->contentLoader->createEntity($entity_type, $test_content);
  }


  /**
   * Confirm createEntity does check for existing entities when enabled.
   *
   * @param string $entity_type
   *   The entity type machine name for the content being tested.
   * @param array $test_content
   *   Import content for this test scenario.
   *
   * @dataProvider contentDataProvider
   *
   * @covers ::createEntity
   * @covers ::existenceCheck
   * @covers ::setExistenceCheck
   */
  public function testCreateEntityDoesCallEntityExistsWhenEnabled($entity_type, array $test_content) {
    // Stub methods used in the buildEntity() method.
    $this->contentLoader = $this->getContentLoaderMock([
      'getContentAttributes',
      'getEntityStorage',
      'entityExists',
    ]);

    // Ensure existence checking should be disabled.
    $this->contentLoader->setExistenceCheck(TRUE);

    // The `entityExists()` method should never be called.
    $this->contentLoader->expects($this->once())
      ->method('entityExists');

    // Override `getContentAttributes()` to return values based on test data.
    $attributes = $this->getContentAttributes($entity_type, $test_content);
    $this->contentLoader
      ->expects($this->once())
      ->method('getContentAttributes')
      ->willReturn($attributes);

    // Mock the entity storage to confirm `create()` was called.
    $storage_handler_mock = $this->getMockForAbstractClass(EntityStorageInterface::class);
    $storage_handler_mock->expects($this->once())
      ->method('create')
      ->with(
        $attributes['property']
      );

    // Return the mocked storage handler for testing.
    $this->contentLoader->expects($this->once())
      ->method('getEntityStorage')
      ->willReturn($storage_handler_mock);

    $this->contentLoader->createEntity($entity_type, $test_content);
  }

  /**
   * Confirm `createEntity()` does not create a new entity if a match is found.
   *
   * @covers ::createEntity
   */
  public function testCreateEntityLoadsMatchingEntityWhenFound() {
    $this->markTestIncomplete();
  }

  /**
   * Confirm `createEntity()` creates a new entity if a match is not found.
   */
  public function testCreateEntityCreatesNewEntityWhenMatchNotFound() {
    $this->markTestIncomplete();
  }

  /**
   * Confirm `createEntity()` does not change an existing entity if loaded.
   */
  public function testCreateEntityDoesNotChangeExistingEntities() {
    $this->markTestIncomplete();
  }

  /**
   * Data provider function to test various content scenarios.
   *
   * @return array
   *   An array of content testing arguments:
   *   - string Entity Type
   *   - array Content data structure
   *
   * @todo Refactor to provide entity definition and content data.
   */
  public function contentDataProvider() {
    $test_content['basic_node'] = [
      'entity' => 'node',
      'status' => 1,
      'title' => 'Test Title',
      'body' => [
        'value' => 'Lorem Ipsum',
        'format' => 'full_html',
      ],
      'field_existing_field' => [
        'value' => 'simple',
      ],
    ];

    return [
      ['node', $test_content['basic_node']],
    ];
  }

  /**
   * Tests the entityExists method.
   *
   * @param bool $expected
   *   The expected result from the entityExists() method using these arguments.
   * @param array $content_data
   *   The content data being tested.
   * @param callable|null $setupCallback
   *   (Optional) A callback function to be used to prepare for this specific
   *   content test.
   *
   * @dataProvider entityExistsDataProvider
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoader::entityExists()
   */
  public function testEntityExists($expected, array $content_data, $setupCallback = NULL) {
    // Execute the callback function for this test case if provided.
    if (is_callable($setupCallback)) {
      call_user_func($setupCallback);
    }

    $entity_type = $content_data['entity'];
    $actual = $this->contentLoader->entityExists($entity_type, $content_data);

    $this->assertEquals($expected, $actual);
    $this->markTestIncomplete();
  }

  /**
   * Data provider to prepare entityExists method tests.
   *
   * @todo Extend data sets for more complete testing.
   */
  public function entityExistsDataProvider() {
    // Paragraphs should always be recreated since they can't reliably be
    // identified as duplicates without false positives.
    $paragraph_test = [
      // Expected result.
      FALSE,
      // Content data.
      [
        'entity' => 'paragraph',
        'type' => 'test_paragraph_bundle',
        'field_title' => [
          'value' => 'Test Title',
        ],
      ],
      // Callback setup.
      NULL,
    ];

    // Media and file entities require special handling to identify matches.
    // @todo Add tests for media and file content.
    $media_test = [];
    $file_test = [];

    // Nodes should match regularly based on available property data.
    // @todo Test node existence checks with an available match.
    $node_match_test = [];
    // @todo Test node existence checks without an available match.
    $node_no_match_test = [];

    return [
      $paragraph_test,
      // $media_test,
      // $file_test,
      // $node_match_test,
      // $node_no_match_test,
    ];
  }

  /**
   * Helper function to provide content attributes based on active test data.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param array $content_data
   *   Content data array being tested.
   *
   * @return array
   *   Test content grouped by properties and fields according to test entity
   *   definitions.
   *
   * @see \Drupal\Tests\yaml_content\Unit\ContentLoader\ExistenceCheckingTest::$testEntityDefinitions
   */
  protected function getContentAttributes($entity_type, array $content_data) {
    $attributes = [];

    $attributes['property'] = array_intersect_key($content_data, $this->testEntityDefinitions[$entity_type]['entity_keys']);
    $attributes['field'] = array_intersect_key($content_data, $this->testEntityDefinitions[$entity_type]['fields']);

    return $attributes;
  }

}
