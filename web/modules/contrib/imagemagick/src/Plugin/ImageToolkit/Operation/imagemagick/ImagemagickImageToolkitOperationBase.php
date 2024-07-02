<?php

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;
use Drupal\imagemagick\ArgumentMode;
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
   * @param int|ArgumentMode|null $mode
   *   (optional) The mode of the argument in the command line. Determines if
   *   the argument should be placed before or after the source image file path.
   *   Defaults to ArgumentMode::PostSource.
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
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   Use ::addArguments() instead.
   *
   * @see https://www.drupal.org/node/3414601
   */
  protected function addArgument(string $argument, int|ArgumentMode|NULL $mode = ArgumentMode::PostSource, int $index = ImagemagickExecArguments::APPEND, array $info = []): ImagemagickExecArguments {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::addArguments() instead. See https://www.drupal.org/node/3414601', E_USER_DEPRECATED);
    if (is_int($mode)) {
      @trigger_error('Passing an integer value for $mode in ' . __METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ArgumentMode instead. See https://www.drupal.org/node/3409254', E_USER_DEPRECATED);
      $mode = match ($mode) {
        // @phpstan-ignore-next-line
        static::PRE_SOURCE => ArgumentMode::PreSource,
        // @phpstan-ignore-next-line
        static::POST_SOURCE => ArgumentMode::PostSource,
        // @phpstan-ignore-next-line
        static::INTERNAL => ArgumentMode::Internal,
        default => ArgumentMode::PreSource,
      };
    }
    $plugin_definition = $this->getPluginDefinition();
    $info = array_merge($info, [
      'image_toolkit_operation' => $plugin_definition['operation'],
      'image_toolkit_operation_plugin_id' => $plugin_definition['id'],
    ]);
    return $this->getToolkit()->arguments()->add($argument, $mode, $index, $info);
  }

  /**
   * Helper to add command line arguments.
   *
   * Adds the originating operation and plugin id to the $info array.
   *
   * @param string[] $arguments
   *   The command line arguments to be added.
   * @param int|ArgumentMode|null $mode
   *   (optional) The mode of the argument in the command line. Determines if
   *   the argument should be placed before or after the source image file path.
   *   Defaults to ArgumentMode::PostSource.
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
  protected function addArguments(array $arguments, int|ArgumentMode|NULL $mode = ArgumentMode::PostSource, int $index = ImagemagickExecArguments::APPEND, array $info = []): ImagemagickExecArguments {
    if (is_int($mode)) {
      @trigger_error('Passing an integer value for $mode in ' . __METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ArgumentMode instead. See https://www.drupal.org/node/3409254', E_USER_DEPRECATED);
      $mode = match ($mode) {
        // @phpstan-ignore-next-line
        static::PRE_SOURCE => ArgumentMode::PreSource,
        // @phpstan-ignore-next-line
        static::POST_SOURCE => ArgumentMode::PostSource,
        // @phpstan-ignore-next-line
        static::INTERNAL => ArgumentMode::Internal,
        default => ArgumentMode::PreSource,
      };
    }
    $plugin_definition = $this->getPluginDefinition();
    $info = array_merge($info, [
      'image_toolkit_operation' => $plugin_definition['operation'],
      'image_toolkit_operation_plugin_id' => $plugin_definition['id'],
    ]);
    return $this->getToolkit()->arguments()->add($arguments, $mode, $index, $info);
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
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   There is no need to escape arguments any more.
   *
   * @see https://www.drupal.org/node/3414601
   */
  protected function escapeArgument(string $argument): string {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. There is no need to escape arguments any more. See https://www.drupal.org/node/3414601', E_USER_DEPRECATED);
    return $this->getToolkit()->arguments()->escape($argument);
  }

}
