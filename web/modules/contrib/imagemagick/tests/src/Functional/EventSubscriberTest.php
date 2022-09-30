<?php

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\imagemagick\Kernel\ToolkitSetupTrait;

/**
 * Tests for ImagemagickEventSubscriber.
 *
 * @group imagemagick
 */
class EventSubscriberTest extends BrowserTestBase {

  use ToolkitSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'imagemagick', 'file_mdm'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->moduleList = \Drupal::service('extension.list.module');
  }

  /**
   * Test module's event subscriber.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testEventSubscriber(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $fmdm = \Drupal::service('file_metadata_manager');

    // Change the Advanced Colorspace setting, must be included in the command
    // line.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('advanced.colorspace', 'GRAY')
      ->save();

    $image_uri = "public://image-test.png";
    $image = $this->imageFactory->get($image_uri);
    if (!$image->isValid()) {
      $this->fail("Could not load image $image_uri.");
    }

    // Check the source colorspace.
    if ($toolkit_settings['binaries'] === 'imagemagick') {
      $this->assertSame('SRGB', $image->getToolkit()->getColorspace());
    }
    else {
      $this->assertNull($image->getToolkit()->getColorspace());
    }

    // Setup a list of arguments.
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");

    // Save the derived image.
    $image->save($image_uri . '.derived');

    // Check expected command line.
    if (substr(PHP_OS, 0, 3) === 'WIN') {
      $expected = "-resize 100x75! -quality 75 -colorspace \"GRAY\"";
    }
    else {
      $expected = "-resize 100x75! -quality 75 -colorspace 'GRAY'";
    }
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));

    // Check that the colorspace has been actually changed in the file.
    Cache::InvalidateTags([
      'config:imagemagick.file_metadata_plugin.imagemagick_identify',
    ]);
    $fmdm->release($image_uri . '.derived');
    $image_md = $fmdm->uri($image_uri . '.derived');
    $image = $this->imageFactory->get($image_uri . '.derived');
    $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $image_md->isMetadataLoaded(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID));
    if ($toolkit_settings['binaries'] === 'imagemagick') {
      $this->assertSame('GRAY', $image->getToolkit()->getColorspace());
    }
    else {
      $this->assertNull($image->getToolkit()->getColorspace());
    }

    // Change the Prepend settings, must be included in the command line.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('prepend', '-debug All')
      ->save();
    $image = $this->imageFactory->get($image_uri);
    $image->getToolkit()->arguments()
      ->add("-resize 100x75!")
      ->add("-quality 75");
    $image->save($image_uri . '.derived');
    if (substr(PHP_OS, 0, 3) === 'WIN') {
      $expected = "-resize 100x75! -quality 75 -colorspace \"GRAY\"";
    }
    else {
      $expected = "-resize 100x75! -quality 75 -colorspace 'GRAY'";
    }
    $this->assertSame('-debug All', $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::PRE_SOURCE));
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));
  }

  /**
   * Test coalescence of Animated GIFs.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testGifCoalesce(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $image_uri = $this->moduleList->getPath('imagemagick') . '/misc/test-multi-frame.gif';

    // By default, no coalesce of animated GIFs.
    $image = $this->imageFactory->get($image_uri);
    $image->getToolkit()->arguments()->add("-resize 100x75!");
    $image->save("public://imagetest/coalesced.gif");
    $expected = "-resize 100x75! -quality 100";
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));

    // Change the Advanced Coalesce setting, '-coalesce' must now be included
    // in the command line.
    \Drupal::configFactory()->getEditable('imagemagick.settings')
      ->set('advanced.coalesce', TRUE)
      ->save();
    $image = $this->imageFactory->get($image_uri);
    $image->getToolkit()->arguments()->add("-resize 100x75!");
    $image->save("public://imagetest/coalesced.gif");
    $expected = "-coalesce -resize 100x75! -quality 100";
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));

    // Single frame GIF should not be coalesceable.
    $image = $this->imageFactory->get("public://image-test.gif");
    $image->getToolkit()->arguments()->add("-resize 100x75!");
    $image->save("public://imagetest/coalesced.gif");
    $expected = "-resize 100x75! -quality 100";
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));

    // PNG should not be coalesceable.
    $image = $this->imageFactory->get("public://image-test.png");
    $image->getToolkit()->arguments()->add("-resize 100x75!");
    $image->save("public://imagetest/coalesced.png");
    $expected = "-resize 100x75! -quality 100";
    $this->assertSame($expected, $image->getToolkit()->arguments()->toString(ImagemagickExecArguments::POST_SOURCE));
  }

}
