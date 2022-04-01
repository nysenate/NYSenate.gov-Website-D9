<?php

namespace Drupal\Tests\yaml_content\Unit\Plugin\yaml_content\process;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\yaml_content\Traits\LoadFixturesTrait;
use Drupal\yaml_content\ContentLoader\ContentLoader;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\yaml_content\process\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test file processing.
 *
 * Note: This only tests failures because to test passes would require writing
 * to the file system.
 *
 * @group yaml_content
 *
 * @coversDefaultClass \Drupal\yaml_content\Plugin\yaml_content\process\File
 */
class FileTest extends UnitTestCase {

  use LoadFixturesTrait;

  /**
   * The file process plugin being tested.
   *
   * @var \Drupal\yaml_content\Plugin\yaml_content\process\File
   */
  protected $filePlugin;

  /**
   * Setup the file process plugin for all tests.
   */
  public function setUp() {
    parent::setUp();
    $args = [
      'my_entity',
      ['filename' => 'test_file.txt'],
    ];
    $this->filePlugin = new File($args, '', []);
  }

  /**
   * @covers ::process
   */
  public function testProcessMissingLoader() {
    $context = new ProcessingContext();
    $data = [];
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Missing content loader context.');
    $this->filePlugin->process($context, $data);
  }

  /**
   * @covers ::process
   */
  public function testProcessMissingField() {
    $context = new ProcessingContext();
    $context->setContentLoader(new ContentLoader($this->prophesize(ContainerInterface::class)->reveal()));
    $context->getContentLoader()->setContentPath(realpath($this->getFixturePath() . '/../../'));
    $data = [];
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Missing field context.');
    $this->filePlugin->process($context, $data);
  }

}
