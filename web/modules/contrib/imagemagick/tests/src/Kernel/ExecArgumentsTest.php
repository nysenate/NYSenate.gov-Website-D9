<?php

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\imagemagick\ImagemagickExecArguments;

/**
 * Tests for ImagemagickExecArguments.
 *
 * @group imagemagick
 */
class ExecArgumentsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['imagemagick', 'file_mdm', 'sophron'];

  /**
   * Test arguments handling.
   */
  public function testArguments(): void {
    // Get an empty Image object.
    $arguments = new ImagemagickExecArguments(\Drupal::service('imagemagick.exec_manager'));

    // Setup a list of arguments.
    $arguments
      ->add("-resize 100x75!")
      // Internal argument.
      ->add("INTERNAL", ImagemagickExecArguments::INTERNAL)
      ->add("-quality 75")
      // Prepend argument.
      ->add("-hoxi 76", ImagemagickExecArguments::POST_SOURCE, 0)
      // Pre source argument.
      ->add("-density 25", ImagemagickExecArguments::PRE_SOURCE)
      // Another internal argument.
      ->add("GATEAU", ImagemagickExecArguments::INTERNAL)
      // Another pre source argument.
      ->add("-auchocolat 90", ImagemagickExecArguments::PRE_SOURCE)
      // Add two arguments with additional info.
      ->add(
        "-addz 150",
        ImagemagickExecArguments::POST_SOURCE,
        ImagemagickExecArguments::APPEND,
        [
          'foo' => 'bar',
          'qux' => 'der',
        ]
      )
      ->add(
        "-addz 200",
        ImagemagickExecArguments::POST_SOURCE,
        ImagemagickExecArguments::APPEND,
        [
          'wey' => 'lod',
          'foo' => 'bar',
        ]
      );

    // Test find arguments skipping identifiers.
    $this->assertSame([2], array_keys($arguments->find('/^INTERNAL/')));
    $this->assertSame([5], array_keys($arguments->find('/^GATEAU/')));
    $this->assertSame([6], array_keys($arguments->find('/^\-auchocolat/')));
    $this->assertSame([7, 8], array_keys($arguments->find('/^\-addz/')));
    $this->assertSame([7, 8], array_keys($arguments->find('/.*/', NULL, ['foo' => 'bar'])));
    $this->assertSame([], $arguments->find('/.*/', NULL, ['arw' => 'moo']));

    // Check resulting command line strings.
    $this->assertSame('-density 25 -auchocolat 90', $arguments->toString(ImagemagickExecArguments::PRE_SOURCE));
    $this->assertSame("-hoxi 76 -resize 100x75! -quality 75 -addz 150 -addz 200", $arguments->toString(ImagemagickExecArguments::POST_SOURCE));

    // Add arguments with a specific index.
    $arguments
      ->add("-ix aa", ImagemagickExecArguments::POST_SOURCE, 4)
      ->add("-ix bb", ImagemagickExecArguments::POST_SOURCE, 4);
    $this->assertSame([4, 5], array_keys($arguments->find('/^\-ix/')));
    $this->assertSame("-hoxi 76 -resize 100x75! -quality 75 -ix bb -ix aa -addz 150 -addz 200", $arguments->toString(ImagemagickExecArguments::POST_SOURCE));
  }

}
