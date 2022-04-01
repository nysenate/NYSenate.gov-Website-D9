<?php

namespace Drupal\Tests\yaml_content\Functional\Plugin\yaml_content\process;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File as FileEntity;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\yaml_content\Traits\LoadFixturesTrait;
use Drupal\yaml_content\ContentLoader\ContentLoader;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\yaml_content\process\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test file processing.
 *
 * Note: This only tests writing a successful file.
 *
 * @todo Test image special case?
 *
 * @group yaml_content
 * @coversDefaultClass \Drupal\yaml_content\Plugin\yaml_content\process\File
 */
class FileTest extends BrowserTestBase {

  use LoadFixturesTrait;

  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test file processing.
   *
   * @covers ::process
   */
  public function testProcess() {
    $args = [
      'my_entity',
      ['filename' => 'test_file.txt'],
    ];
    $file = new File($args, '', []);
    $context = new ProcessingContext();
    $context->setContentLoader(new ContentLoader($this->prophesize(ContainerInterface::class)->reveal()));
    $context->getContentLoader()->setContentPath(realpath($this->getFixturePath() . '/../../'));
    $field = new FieldItemList(new BaseFieldDefinition([], new DataDefinition()));
    $context->setField($field);

    $data = [];
    $expected_fid = $file->process($context, $data);
    $this->assertEquals(['target_id' => $expected_fid], $data);

    $storage = \Drupal::entityTypeManager()->getStorage('file');
    $file = $storage->load($expected_fid);
    $this->assertEquals($args[1]['filename'], $file->getFilename());
    $this->assertFileExists($file->getFileUri());
  }

}
