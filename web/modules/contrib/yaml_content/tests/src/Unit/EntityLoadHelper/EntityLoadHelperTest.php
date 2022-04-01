<?php

namespace Drupal\Tests\yaml_content\Unit\EntityLoadHelper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\yaml_content\Traits\LoadFixturesTrait;
use Drupal\yaml_content\Service\EntityLoadHelper;

/**
 * Test functionality of the EntityLoadHelper class.
 *
 * @coversDefaultClass Drupal\yaml_content\Service\EntityLoadHelper
 * @group yaml_content
 */
class EntityLoadHelperTest extends UnitTestCase {

  use LoadFixturesTrait;

  /**
   * A prepared EntityLoadHelper object for testing.
   *
   * @var \Drupal\yaml_content\Service\EntityLoadHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loadHelper;

  /**
   * Mock the EntityLoadHelper class to support test inspections.
   *
   * Mock the EntityLoadHelper class with a configurable list of stubbed methods.
   *
   * @param array|null $stubbed_methods
   *   (Optional) An array of method names to leave active on the mock object.
   *   All other declared methods on the ContentLoader class will be stubbed. If
   *   this argument is omitted all methods are mocked and execute their
   *   original code.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked ContentLoader object with
   */
  protected function getEntityLoadHelperMock($stubbed_methods = NULL) {
    // Partially mock the ContentLoader for testing specific methods.
    $mock = $this->getMockBuilder(EntityLoadHelper::class)
      ->disableOriginalConstructor()
      ->setMethods($stubbed_methods)
      ->getMock();

    return $mock;
  }

  /**
   * Test the entity type manager is lazy loaded upon request.
   *
   * @covers ::getEntityTypeManager
   */
  public function testEntityTypeManagerIsLazyLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test the entity field manager is lazy loaded upon request.
   *
   * @covers ::getEntityFieldManager
   */
  public function testEntityFieldManagerIsLazyLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test entityExists uses content data to load matching entities.
   *
   * @covers ::entityExists
   * @covers ::loadEntity
   */
  public function testEntityExistsAttemptsToLoadEntity() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadEntity',
    ]);

    // Prepare arguments to be tested.
    $entity_type = 'text_entity';
    $content_data = [
      'entity' => 'test_entity',
      'type' => 'tester',
      'name' => 'Test Entity',
    ];

    // Prepare expectations.
    $this->loadHelper->expects($this->once())
      ->method('loadEntity')
      ->with(
        $entity_type,
        $content_data
      );

    // Execute the method.
    $this->loadHelper->entityExists($entity_type, $content_data);
  }

  /**
   * Test entityExists method returns true when an entity is loaded.
   *
   * @covers ::entityExists
   */
  public function testEntityExistsReturnsTrueWhenAnEntityIsLoaded() {
    // Mock the load helper for testing.
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadEntity',
    ]);

    $this->loadHelper->method('loadEntity')
      ->willReturn($this->getEntityMock());

    $this->assertTrue($this->loadHelper->entityExists('test_entity', []));
  }

  /**
   * Test entityExists method returns false when an entity is not loaded.
   *
   * @covers ::entityExists
   */
  public function testEntityExistsReturnsFalseWhenAnEntityIsNotLoaded() {
    // Mock the load helper for testing.
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadEntity',
    ]);

    $this->loadHelper->method('loadEntity')
      ->willReturn(FALSE);

    $this->assertFalse($this->loadHelper->entityExists('test_entity', []));
  }

  /**
   * An entity is searched by UUID first if one is provided.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityLoadsUuidFirstIfAvailable() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    $content_data = [
      'entity' => 'test_entity',
      // Include a UUID property.
      'uuid' => '3c6485e4-69a3-429d-8ab1-3e7df48747bc'
    ];

    $this->loadHelper->expects($this->once())
      ->method('loadByUuid')
      ->with(
        $entity_type,
        $content_data['uuid']
      );

    // Execute the method.
    $this->loadHelper->loadEntity($entity_type, $content_data);
  }

  /**
   * An entity is not searched by properties if UUID is provided.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityDoesntSearchTwiceIfUuidIsProvided() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    $content_data = [
      'entity' => 'test_entity',
      // Include a UUID property.
      'uuid' => '3c6485e4-69a3-429d-8ab1-3e7df48747bc'
    ];

    $this->loadHelper->expects($this->never())
      ->method('loadByProperties');

    // Execute the method.
    $this->loadHelper->loadEntity($entity_type, $content_data);
  }

  /**
   * An entity is not searched by UUID if no UUID is provided.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityDoesntSearchTwiceIfNoUuidIsProvided() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    // Do not include a UUID property.
    $content_data = [
      'entity' => 'test_entity',
    ];

    $this->loadHelper->expects($this->never())
      ->method('loadByUuid');

    // Execute the method.
    $this->loadHelper->loadEntity($entity_type, $content_data);
  }

  /**
   * An entity is searched by properties if no UUID is defined.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityLoadsByPropertiesIfUuidIsUnavailable() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    // Do not include a UUID property.
    $content_data = [
      'entity' => 'test_entity',
    ];

    $this->loadHelper->expects($this->once())
      ->method('loadByProperties')
      ->with(
        $entity_type,
        $content_data
      );

    // Execute the method.
    $this->loadHelper->loadEntity($entity_type, $content_data);
  }

  /**
   * Test that loadEntity returns false if UUID searching returned no matches.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityWithUuidReturnsFalseWithNoMatches() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    $content_data = [
      'entity' => 'test_entity',
      // Include a UUID property.
      'uuid' => '3c6485e4-69a3-429d-8ab1-3e7df48747bc'
    ];

    // Mock that loadByUuid found no matches.
    $this->loadHelper->method('loadByUuid')
      ->willReturn(FALSE);

    // Execute the method.
    $actual = $this->loadHelper->loadEntity($entity_type, $content_data);

    // Confirm the return value.
    $this->assertFalse($actual);
  }

  /**
   * Test that loadEntity returns matched entity if UUID searching returned a match.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityWithUuidReturnsMatchedEntity() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    $content_data = [
      'entity' => 'test_entity',
      // Include a UUID property.
      'uuid' => '3c6485e4-69a3-429d-8ab1-3e7df48747bc'
    ];

    // Mock that loadByUuid found a match.
    $matched_entity = $this->getEntityMock();
    $this->loadHelper->method('loadByUuid')
      ->willReturn($matched_entity);

    // Execute the method.
    $actual = $this->loadHelper->loadEntity($entity_type, $content_data);

    // Confirm the return value.
    $this->assertSame($matched_entity, $actual);
  }

  /**
   * Test that loadEntity returns false if property searching returned no matches.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityWithPropertiesReturnsFalseWithNoMatches() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    // Do not include a UUID property.
    $content_data = [
      'entity' => 'test_entity',
    ];

    // Mock that loadByProperties found no matches.
    $this->loadHelper->method('loadByProperties')
      ->willReturn(FALSE);

    // Execute the method.
    $actual = $this->loadHelper->loadEntity($entity_type, $content_data);

    // Confirm the return value.
    $this->assertFalse($actual);
  }

  /**
   * Test that loadEntity returns matched entity if property searching returned a match.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityWithPropertiesdReturnsMatchedEntity() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'loadByUuid',
      'loadByProperties',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    // Do not include a UUID property.
    $content_data = [
      'entity' => 'test_entity',
    ];

    // Mock that loadByProperties found a match.
    $matched_entity = $this->getEntityMock();
    $this->loadHelper->method('loadByProperties')
      ->willReturn($matched_entity);

    // Execute the method.
    $actual = $this->loadHelper->loadEntity($entity_type, $content_data);

    // Confirm the return value.
    $this->assertSame($matched_entity, $actual);
  }

  /**
   * Test that UUID search only includes the UUID and entity type.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidSearchesByUuidOnly() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns only the first match.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidReturnsOnlyOneMatch() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns false if no match is found.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidReturnsFalseIfNoMatchIsFound() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test that property search only includes content property values.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesSearchesByPropertiesOnly() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByProperties returns only the first match.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesReturnsOnlyOneMatch() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns false if no match is found.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesReturnsFalseIfNoMatchIsFound() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test extractContentProperties returns property attributes.
   *
   * @covers ::extractContentProperties
   */
  public function testExtractContentPropertiesOnlyReturnsProperties() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'categorizeAttributes',
    ]);

    // Prepare the parameters.
    $entity_type = 'test_entity';
    // Do not include a UUID property.
    $content_data = [
      'entity' => 'test_entity',
      'type' => 'test_bundle',
      'status' => '1',
      'field_title' => 'Test Title',
    ];

    // Mock the categorizeAttributes return value.
    $results = [
      'property' => [
        'entity' => 'test_entity',
        'type' => 'test_bundle',
        'status' => '1',
      ],
      'field' => [
        'field_title' => 'Test Title',
      ],
      'other' => [],
    ];
    $this->loadHelper->method('categorizeAttributes')
      ->willReturn($results);

    // Execute the method.
    $actual = $this->loadHelper->extractContentProperties($entity_type, $content_data);

    // Confirm the return value.
    $this->assertSame($results['property'], $actual);
  }

  /**
   * Test categorizeAttributes always returns three attribute categories.
   *
   * @covers ::categorizeAttributes
   * @covers ::identifyAttributeType
   *
   * @dataProvider attributeCategorizationProvider
   */
  public function testCategorizeAttributesAlwaysReturnsThreeKeys($entity_type, $content, $expected) {
    $this->setUpCategorizeAttributesTests();

    // Execute the method.
    $actual = $this->loadHelper->categorizeAttributes($entity_type, $content);

    // Confirm the returned keys.
    $this->assertSame(array_keys($expected), array_keys($actual), 'categorizeAttributes method did not return the expected keys.');
  }

  /**
   * Test categorizeAttributes identifies properties as expected.
   *
   * @covers ::categorizeAttributes
   * @covers ::identifyAttributeType
   *
   * @dataProvider attributeCategorizationProvider
   */
  public function testCategorizeAttributesProperlyIdentifiesProperties($entity_type, $content, $expected) {
    $this->setUpCategorizeAttributesTests();

    // Execute the method.
    $actual = $this->loadHelper->categorizeAttributes($entity_type, $content);

    $this->assertArrayEquals($expected['property'], $actual['property']);
  }

  /**
   * Test categorizeAttributes identifies fields as expected.
   *
   * @covers ::categorizeAttributes
   * @covers ::identifyAttributeType
   *
   * @dataProvider attributeCategorizationProvider
   */
  public function testCategorizeAttributesProperlyIdentifiesFields($entity_type, $content, $expected) {
    $this->setUpCategorizeAttributesTests();

    // Execute the method.
    $actual = $this->loadHelper->categorizeAttributes($entity_type, $content);

    $this->assertArrayEquals($expected['field'], $actual['field']);
  }

  /**
   * Test categorizeAttributes identifies "other" attributes as expected.
   *
   * @covers ::categorizeAttributes
   * @covers ::identifyAttributeType
   *
   * @dataProvider attributeCategorizationProvider
   */
  public function testCategorizeAttributesProperlyIdentifiesOther($entity_type, $content, $expected) {
    $this->setUpCategorizeAttributesTests();

    // Execute the method.
    $actual = $this->loadHelper->categorizeAttributes($entity_type, $content);

    $this->assertArrayEquals($expected['other'], $actual['other']);
  }

  /**
   * Data provider to test attribute categorization.
   */
  public function attributeCategorizationProvider() {
    $fixture = $this->loadFixtureContent('attribute_categorization.assertions');

    $assertions = [];
    foreach ($fixture as $assertion) {
      $assertions[] = [
        $assertion['entity_type'],
        $assertion['content'],
        $assertion['expected'],
      ];
    }

    return $assertions;
  }

  /**
   * Get a mocked entity definition.
   *
   * @param string $entity_type
   *   The identifier for the entity type definition being mocked.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|ContentEntityTypeInterface
   *   The mock for the entity definition.
   */
  public function getEntityDefinition($entity_type) {
    $definition = $this->loadFixtureContent('entity_definitions', [$entity_type]);

    $mock = $this->getMockForAbstractClass(ContentEntityTypeInterface::class);

    $mock->method('hasKey')
      ->willReturnCallback(function ($key) use ($definition) {
        return in_array($key, $definition['entity_keys']);
      });

    $mock->method('getKeys')
      ->willReturn($definition['entity_keys']);

    return $mock;
  }

  /**
   * Load fixture data for mapped entity fields.
   *
   * @param $entity_type
   *   The identifier for the entity type field list being loaded.
   *
   * @return array
   *   An array of fields for the specified entity type.
   */
  public function getEntityFields($entity_type) {
    $field_map = $this->loadFixtureContent('field_list', [$entity_type]);

    return $field_map;
  }

  /**
   * Get a mock for an entity storage handler.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|EntityStorageInterface
   */
  protected function getEntityStorageMock() {
    $mock = $this->getMockForAbstractClass(EntityStorageInterface::class);

    return $mock;
  }

  /**
   * Get a mock for an entity.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject|ContentEntityInterface
   */
  protected function getEntityMock() {
    $mock = $this->getMockForAbstractClass(ContentEntityInterface::class);

    return $mock;
  }

  /**
   * Prepare the EntityLoadHelper mock for testing categorizeAttributes.
   */
  protected function setUpCategorizeAttributesTests() {
    $this->loadHelper = $this->getEntityLoadHelperMock([
      'getEntityFields',
      'getEntityDefinition',
    ]);

    // Mock the getEntityFields return value.
    $this->loadHelper->method('getEntityFields')
      ->willReturnCallback([$this, 'getEntityFields']);

    // Mock the getEntityDefinition return value.
    $this->loadHelper->method('getEntityDefinition')
      ->willReturnCallback([$this, 'getEntityDefinition']);
  }

}
