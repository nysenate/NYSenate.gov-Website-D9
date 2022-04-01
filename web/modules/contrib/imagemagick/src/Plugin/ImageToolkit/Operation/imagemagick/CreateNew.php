<?php

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Component\Utility\Color;

/**
 * Defines imagemagick CreateNew operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagemagick_create_new",
 *   toolkit = "imagemagick",
 *   operation = "create_new",
 *   label = @Translation("Set a new image"),
 *   description = @Translation("Creates a new transparent resource and sets it for the image.")
 * )
 */
class CreateNew extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'width' => [
        'description' => 'The width of the image, in pixels',
      ],
      'height' => [
        'description' => 'The height of the image, in pixels',
      ],
      'extension' => [
        'description' => 'The extension of the image file (e.g. png, gif, etc.)',
        'required' => FALSE,
        'default' => 'png',
      ],
      'transparent_color' => [
        'description' => 'The RGB hex color for GIF transparency',
        'required' => FALSE,
        'default' => '#ffffff',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Assure extension is supported.
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException("Invalid extension ('{$arguments['extension']}') specified for the image 'create_new' operation");
    }

    // Assure integers for width and height.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'create_new' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ({$arguments['height']}) specified for the image 'create_new' operation");
    }

    // Assure transparent color is a valid hex string.
    if ($arguments['transparent_color'] && !Color::validateHex($arguments['transparent_color'])) {
      throw new \InvalidArgumentException("Invalid transparent color ({$arguments['transparent_color']}) specified for the image 'create_new' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Reset the image properties and any processing argument.
    $format = $this->getToolkit()->getExecManager()->getFormatMapper()->getFormatFromExtension($arguments['extension']) ?: '';
    $this->getToolkit()->reset($arguments['width'], $arguments['height'], $format);

    // Add the required arguments to allow Imagemagick to create an image
    // from scratch.
    $arg = '-size ' . $arguments['width'] . 'x' . $arguments['height'];

    // Transparent color syntax for GIF files differs by package.
    if ($arguments['extension'] === 'gif') {
      switch ($this->getToolkit()->getExecManager()->getPackage()) {
        case 'imagemagick':
          $arg .= ' xc:transparent -transparent-color ' . $this->escapeArgument($arguments['transparent_color']);
          break;

        case 'graphicsmagick':
          $arg .= ' xc:' . $this->escapeArgument($arguments['transparent_color']) . ' -transparent ' . $this->escapeArgument($arguments['transparent_color']);
          break;

      }
    }
    else {
      $arg .= ' xc:transparent';
    }

    $this->addArgument($arg);
    return TRUE;
  }

}
