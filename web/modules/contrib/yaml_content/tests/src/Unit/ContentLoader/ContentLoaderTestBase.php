<?php

namespace Drupal\Tests\yaml_content\Unit\ContentLoader;

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Drupal\yaml_content\ContentLoader\ContentLoader;

/**
 * Base test class for all ContentLoader testing.
 */
abstract class ContentLoaderTestBase extends UnitTestCase {

  /**
   * A prepared ContentLoader object for testing.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoader
   */
  protected $contentLoader;

  /**
   * Mock the ContentLoader class to support test inspections.
   *
   * Mock the ContentLoader class with a configurable list of stubbed methods.
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
  protected function getContentLoaderMock($stubbed_methods = NULL) {
    // Partially mock the ContentLoader for testing specific methods.
    $this->contentLoader = $this->getMockBuilder(ContentLoader::class)
      ->disableOriginalConstructor()
      ->setMethods($stubbed_methods)
      ->getMock();

    return $this->contentLoader;
  }

  /**
   * Create a test file with specified contents for testing.
   *
   * @param string $filename
   *   The name of the test file to be created.
   * @param string $contents
   *   The contents to populate into the test file.
   *
   * @return $this
   */
  protected function createContentTestFile($filename, $contents) {
    vfsStream::newFile($filename)
      ->withContent($contents)
      ->at($this->root->getChild('content'));

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Prepare the directory structure.
    $this->root = vfsStream::setup('root');
    vfsStream::newDirectory('content')
      ->at($this->root);

    $this->contentLoader = $this->getContentLoaderMock();
  }

}
