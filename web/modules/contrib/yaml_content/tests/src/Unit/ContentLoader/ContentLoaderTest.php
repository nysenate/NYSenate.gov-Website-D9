<?php

namespace Drupal\Tests\yaml_content\Unit\ContentLoader;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\yaml_content\ContentLoader\ContentLoader;

/**
 * Test generic functionality of the ContentLoader class.
 *
 * @coversDefaultClass \Drupal\yaml_content\ContentLoader\ContentLoader
 * @group yaml_content
 */
class ContentLoaderTest extends ContentLoaderTestBase {

  /**
   * Test the setContentPath() method.
   *
   * @covers ::setContentPath
   */
  public function testSetPath() {
    $this->contentLoader->setContentPath($this->root->url());
    $reflected_path = (new \ReflectionObject($this->contentLoader))->getProperty('path');
    $reflected_path->setAccessible(TRUE);
    $this->assertEquals($this->root->url(), $reflected_path->getValue($this->contentLoader));
  }

  /**
   * Test general behavior of the parseContent() method.
   *
   * @covers ::parseContent
   *
   * @todo Test if $contentPath is not set
   * @todo Handle parse failure
   * @todo Test no array at top level of content
   * @todo Confirm array structure loaded
   */
  public function testParseContent() {
    $this->markTestIncomplete();
  }

  /**
   * Tests behavior when a content file is unavailable.
   */
   public function testMissingContentFile() {
     $test_file = 'missing.content.yml';

     // Confirm the file is not actually present.
     $this->assertFalse($this->root->hasChild('content/missing.content.yml'));

     // Prepare the path for the missing content file.
     $this->contentLoader->setContentPath($this->root->url());

     // Parse the test file expecting an error for the missing file.
     $this->expectException(\PHPUnit\Framework\Error\Warning::class);
     $this->contentLoader->parseContent($test_file);
   }

  /**
   * Tests the correct return value when parsing an empty file.
   *
   * When parsing an empty file an empty array should be returned.
   */
  public function testEmptyContentFile() {
    // Prepare an empty content file for parsing.
    $test_file = 'emptyFile.content.yml';
    $this->createContentTestFile($test_file, '');

    // Get the mock content loader.
    $this->contentLoader = $this->getContentLoaderMock(['getEventDispatcher']);

    // Stub event dispatching.
    $event_dispatcher_mock = $this->createMock(ContainerAwareEventDispatcher::class);
    $this->contentLoader->method('getEventDispatcher')
      ->willReturn($event_dispatcher_mock);

    // Prepare and parse the empty content file.
    $this->contentLoader->setContentPath($this->root->url());
    $parsed_content = $this->contentLoader->parseContent($test_file);

    // Confirm an empty array was returned.
    $this->assertArrayEquals([], $parsed_content, 'Empty content files return an empty array.');
  }

  /**
   * Test the entry point content loading behavior.
   *
   * @covers ::loadContent
   */
  public function testLoadContent() {
    $this->markTestIncomplete();
  }

  /**
   * @covers ::populateField
   */
  public function testPopulateFieldCardinalityZero() {
    $field_definition = new BaseFieldDefinition();
    $field_definition->setCardinality(0);
    $field = new FieldItemList($field_definition, 'foobar');
    $field_data = [];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("'foobar' cannot hold any values.");
    $this->contentLoader->populateField($field, $field_data);
  }

  /**
   * @covers ::populateField
   */
  public function testPopulateFieldCardinalityTooMuchData() {
    $field_definition = new BaseFieldDefinition();
    $field_definition->setCardinality(1);
    $field = new FieldItemList($field_definition, 'foobar');
    $field_data = [[], [] , []];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("'foobar' cannot hold more than 1 values. 3 values were parsed from the YAML file.");
    $this->contentLoader->populateField($field, $field_data);
  }

  /**
   * @covers ::populateField
   */
  public function testPopulateFieldProcess() {
    $field_definition = new BaseFieldDefinition();
    $field_definition->setCardinality(1);
    $field = new FieldItemList($field_definition, 'foobar');
    $field_data = [[]];
    $this->markTestIncomplete('We cannot easily test processing is triggered because we cannot inject a Plugin Manager yet.');
    $this->contentLoader->populateField($field, $field_data);
  }

}
