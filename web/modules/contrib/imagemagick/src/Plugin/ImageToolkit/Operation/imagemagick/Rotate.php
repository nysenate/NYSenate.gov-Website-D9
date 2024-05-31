<?php

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Component\Utility\Color;
use Drupal\Component\Utility\Rectangle;

@trigger_error('\Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick\Rotate is deprecated in imagemagick:8.x-3.3 and is removed from imagemagick:4.0.0. Use the rotate operation provided by the Image Effects module instead. See https://www.drupal.org/project/imagemagick/issues/3251438', E_USER_DEPRECATED);

/**
 * Defines imagemagick Rotate operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagemagick_rotate",
 *   toolkit = "imagemagick",
 *   operation = "rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotates an image by the given number of degrees.")
 * )
 */
class Rotate extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return [
      'degrees' => [
        'description' => 'The number of (clockwise) degrees to rotate the image',
      ],
      'background' => [
        'description' => "A string specifying the hexadecimal color code to use as background for the uncovered area of the image after the rotation. E.g. '#000000' for black, '#ff00ff' for magenta, and '#ffffff' for white. For images that support transparency, this will default to transparent white",
        'required' => FALSE,
        'default' => NULL,
      ],
      'resize_filter' => [
        'description' => 'An optional filter to apply for the resize',
        'required' => FALSE,
        'default' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments) {
    // Validate or set background color argument.
    if (!empty($arguments['background'])) {
      // Validate the background color.
      if (!Color::validateHex($arguments['background'])) {
        throw new \InvalidArgumentException("Invalid color '{$arguments['background']}' specified for the 'rotate' operation.");
      }
    }
    else {
      // Background color is not specified: use transparent.
      $arguments['background'] = 'transparent';
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments) {
    // Rotate.
    $arg = '-background ' . $this->escapeArgument($arguments['background']);
    $arg .= ' -rotate ' . $arguments['degrees'];
    $arg .= ' +repage';
    $this->addArgument($arg);

    // Need to resize the image after rotation to make sure it complies with
    // the dimensions expected, calculated via the Rectangle class.
    if ($this->getToolkit()->getWidth() && $this->getToolkit()->getHeight()) {
      $box = new Rectangle($this->getToolkit()->getWidth(), $this->getToolkit()->getHeight());
      $box = $box->rotate((float) $arguments['degrees']);
      return $this->getToolkit()->apply('resize', [
        'width' => $box->getBoundingWidth(),
        'height' => $box->getBoundingHeight(),
        'filter' => $arguments['resize_filter'],
      ]);
    }

    return TRUE;
  }

}
