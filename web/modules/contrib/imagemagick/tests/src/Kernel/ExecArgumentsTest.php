<?php

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\imagemagick\ArgumentMode;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\KernelTests\KernelTestBase;

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
    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));

    // Setup a list of arguments.
    $arguments
      ->add(["-resize", "100x75!"])
      // Internal argument.
      ->add(["INTERNAL"], ArgumentMode::Internal)
      ->add(["-quality", "75"])
      // Prepend argument.
      ->add(["-hoxi", "76"], ArgumentMode::PostSource, 0)
      // Pre source argument.
      ->add(["-density", "25"], ArgumentMode::PreSource)
      // Another internal argument.
      ->add(["GATEAU"], ArgumentMode::Internal)
      // Another pre source argument.
      ->add(["-auchocolat", "90"], ArgumentMode::PreSource)
      // Add two arguments with additional info.
      ->add(
        ["-addz", "150"],
        ArgumentMode::PostSource,
        ImagemagickExecArguments::APPEND,
        [
          'foo' => 'bar',
          'qux' => 'der',
        ]
      )
      ->add(
        ["-addz", "200"],
        ArgumentMode::PostSource,
        ImagemagickExecArguments::APPEND,
        [
          'wey' => 'lod',
          'foo' => 'bar',
        ]
      );

    // Test find arguments skipping identifiers.
    $this->assertSame([4], array_keys($arguments->find('/^INTERNAL/')));
    $this->assertSame([9], array_keys($arguments->find('/^GATEAU/')));
    $this->assertSame([10], array_keys($arguments->find('/^\-auchocolat/')));
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-addz/')));
    $this->assertSame([12, 13, 14, 15], array_keys($arguments->find('/.*/', NULL, ['foo' => 'bar'])));
    $this->assertSame([], $arguments->find('/.*/', NULL, ['arw' => 'moo']));

    // Check resulting command line strings.
    $this->assertSame('[-density] [25] [-auchocolat] [90]', $arguments->toDebugString(ArgumentMode::PreSource));
    $this->assertSame("[-hoxi] [76] [-resize] [100x75!] [-quality] [75] [-addz] [150] [-addz] [200]", $arguments->toDebugString(ArgumentMode::PostSource));

    // Add arguments with a specific index.
    $arguments
      ->add(["-ix", "aa"], ArgumentMode::PostSource, 12)
      ->add(["-ix", "bb"], ArgumentMode::PostSource, 12);
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-ix/')));
    $this->assertSame("[-hoxi] [76] [-resize] [100x75!] [-quality] [75] [-ix] [bb] [-ix] [aa] [-addz] [150] [-addz] [200]", $arguments->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test arguments handling.
   *
   * @group legacy
   */
  public function testArgumentsLegacy(): void {
    $this->expectDeprecation('Passing an integer value for $mode in Drupal\\imagemagick\\ImagemagickExecArguments::add() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ArgumentMode instead. See https://www.drupal.org/node/3409254');
    $this->expectDeprecation('Passing a string value for $arguments in Drupal\\imagemagick\\ImagemagickExecArguments::add() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Pass an array of space trimmed strings instead. See https://www.drupal.org/node/3414601');
    $this->expectDeprecation('Drupal\\imagemagick\\ImagemagickExecArguments::toString() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::toDebugString() to get a string representing the command parameters for debug purposes. See https://www.drupal.org/node/3414601');
    $this->expectDeprecation('Passing an integer value for $mode in Drupal\\imagemagick\\ImagemagickExecArguments::toString() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ArgumentMode instead. See https://www.drupal.org/node/3409254');

    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));

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
    $this->assertSame([4], array_keys($arguments->find('/^INTERNAL/')));
    $this->assertSame([9], array_keys($arguments->find('/^GATEAU/')));
    $this->assertSame([10], array_keys($arguments->find('/^\-auchocolat/')));
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-addz/')));
    $this->assertSame([12, 13, 14, 15], array_keys($arguments->find('/.*/', NULL, ['foo' => 'bar'])));
    $this->assertSame([], $arguments->find('/.*/', NULL, ['arw' => 'moo']));

    // Check resulting command line strings.
    $this->assertSame('-density 25 -auchocolat 90', $arguments->toString(ImagemagickExecArguments::PRE_SOURCE));
    $this->assertSame("-hoxi 76 -resize 100x75! -quality 75 -addz 150 -addz 200", $arguments->toString(ImagemagickExecArguments::POST_SOURCE));

    // Add arguments with a specific index.
    $arguments
      ->add(["-ix", "aa"], ArgumentMode::PostSource, 12)
      ->add(["-ix", "bb"], ArgumentMode::PostSource, 12);
    $this->assertSame([12, 14], array_keys($arguments->find('/^\-ix/')));
    $this->assertSame("-hoxi 76 -resize 100x75! -quality 75 -ix bb -ix aa -addz 150 -addz 200", $arguments->toString(ImagemagickExecArguments::POST_SOURCE));
  }

  /**
   * Test argument strings with quoted tokens.
   *
   * @group legacy
   */
  public function testQuotedArgumentsLegacy(): void {
    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));
    $arguments->add("This is a string that \"will be\" highlighted when your 'regular expression' matches something.");
    $this->assertSame("[This] [is] [a] [string] [that] [will be] [highlighted] [when] [your] [regular expression] [matches] [something.]", $arguments->toDebugString(ArgumentMode::PostSource));
    $arguments->reset();
    $arguments->add("This is \"also \\\"valid\\\"\" and 'more \\'valid\\'' as a string.");
    $this->assertSame("[This] [is] [also \"valid\"] [and] [more 'valid'] [as] [a] [string.]", $arguments->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test argument escaping.
   *
   * @group legacy
   */
  public function testArgumentsEscapingLegacy(): void {
    $arguments = new ImagemagickExecArguments(\Drupal::service(ImagemagickExecManagerInterface::class));

    $arguments->add("-morphology Convolve '3x3:-0.1,-0.1,-0.1 -0.1,1.8,-0.1 -0.1,-0.1,-0.1'");
    $this->assertSame("[-morphology] [Convolve] [3x3:-0.1,-0.1,-0.1 -0.1,1.8,-0.1 -0.1,-0.1,-0.1]", $arguments->toDebugString(ArgumentMode::PostSource));

    $arguments->reset();

    $arg = '';
    $arg .= '-fill ' . $arguments->escape('#000000FF');
    $arguments->add($arg . ' -draw ' . $arguments->escape("ellipse 100,100 20,20 0,360"));
    $arg = '';
    $arg .= '-fill ' . $arguments->escape('#FF0000FF');
    $arguments->add($arg . ' -draw ' . $arguments->escape("polygon 0,0 88,0 88,599 0,599"));
    $this->assertSame("[-fill] [#000000FF] [-draw] [ellipse 100,100 20,20 0,360] [-fill] [#FF0000FF] [-draw] [polygon 0,0 88,0 88,599 0,599]", $arguments->toDebugString(ArgumentMode::PostSource));
  }

}
