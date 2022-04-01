<?php

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;
use Drupal\imagemagick\ImagemagickExecArguments;

/**
 * Base image toolkit operation class for Imagemagick.
 */
abstract class ImagemagickImageToolkitOperationBase extends ImageToolkitOperationBase {

  /**
   * The correctly typed image toolkit for imagemagick operations.
   *
   * @return \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit
   *   The correctly typed image toolkit for imagemagick operations.
   */
  // @codingStandardsIgnoreStart
  protected function getToolkit() {
    return parent::getToolkit();
  }
  // @codingStandardsIgnoreEnd

  /**
   * Helper to add a command line argument.
   *
   * Adds the originating operation and plugin id to the $info array.
   *
   * @param string $argument
   *   The command line argument to be added.
   * @param int $mode
   *   (optional) The mode of the argument in the command line. Determines if
   *   the argument should be placed before or after the source image file path.
   *   Defaults to ImagemagickExecArguments::POST_SOURCE.
   * @param int $index
   *   (optional) The position of the argument in the arguments array.
   *   Reflects the sequence of arguments in the command line. Defaults to
   *   ImagemagickExecArguments::APPEND.
   * @param array $info
   *   (optional) An optional array with information about the argument.
   *   Defaults to an empty array.
   *
   * @return \Drupal\imagemagick\ImagemagickExecArguments
   *   The Imagemagick arguments.
   */
  protected function addArgument(string $argument, int $mode = ImagemagickExecArguments::POST_SOURCE, int $index = ImagemagickExecArguments::APPEND, array $info = []): ImagemagickExecArguments {
    $plugin_definition = $this->getPluginDefinition();
    $info = array_merge($info, [
      'image_toolkit_operation' => $plugin_definition['operation'],
      'image_toolkit_operation_plugin_id' => $plugin_definition['id'],
    ]);
    return $this->getToolkit()->arguments()->add($argument, $mode, $index, $info);
  }

  /**
   * Helper to escape a command line argument.
   *
   * @param string $argument
   *   The string to escape.
   *
   * @return string
   *   An escaped string for use in the
   *   ImagemagickExecManagerInterface::execute method.
   */
  protected function escapeArgument(string $argument): string {
    return $this->getToolkit()->arguments()->escape($argument);
  }

}
