<?php

namespace Drupal\imagemagick;

/**
 * Provides an interface for ImageMagick execution managers.
 */
interface ImagemagickExecManagerInterface {

  /**
   * Returns the format mapper.
   *
   * @return \Drupal\imagemagick\ImagemagickFormatMapperInterface
   *   The format mapper service.
   */
  public function getFormatMapper(): ImagemagickFormatMapperInterface;

  /**
   * Sets the execution timeout (max. runtime).
   *
   * To disable the timeout, set this value to null.
   *
   * @param int|null $timeout
   *   The timeout in seconds.
   *
   * @return $this
   */
  public function setTimeout(int $timeout): ImagemagickExecManagerInterface;

  /**
   * Gets the binaries package in use.
   *
   * @param string $package
   *   (optional) Force the graphics package.
   *
   * @return string
   *   The default package ('imagemagick'|'graphicsmagick'), or the $package
   *   argument.
   */
  public function getPackage(string $package = NULL): string;

  /**
   * Gets a translated label of the binaries package in use.
   *
   * @param string $package
   *   (optional) Force the package.
   *
   * @return string
   *   A translated label of the binaries package in use, or the $package
   *   argument.
   */
  public function getPackageLabel(string $package = NULL): string;

  /**
   * Verifies file path of the executable binary by checking its version.
   *
   * @param string $path
   *   The user-submitted file path to the convert binary.
   * @param string $package
   *   (optional) The graphics package to use.
   *
   * @return array
   *   An associative array containing:
   *   - output: The shell output of 'convert -version', if any.
   *   - errors: A list of error messages indicating if the executable could
   *     not be found or executed.
   */
  public function checkPath(string $path, string $package = NULL): array;

  /**
   * Executes the convert executable as shell command.
   *
   * @param string $command
   *   The executable to run.
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string &$output
   *   (optional) A variable to assign the shell stdout to, passed by
   *   reference.
   * @param string &$error
   *   (optional) A variable to assign the shell stderr to, passed by
   *   reference.
   * @param string $path
   *   (optional) A custom file path to the executable binary.
   *
   * @return bool
   *   TRUE if the command succeeded, FALSE otherwise. The error exit status
   *   code integer returned by the executable is logged.
   */
  public function execute(string $command, ImagemagickExecArguments $arguments, string &$output = NULL, string &$error = NULL, string $path = NULL): bool;

  /**
   * Executes a command on the operating system.
   *
   * This differs from ::runOsCommand in the sense that here the command to be
   * executed and its arguments are passed separately.
   *
   * @param string $command
   *   The command to run.
   * @param string $arguments
   *   The arguments of the command to run.
   * @param string $id
   *   An identifier for the process to be spawned on the operating system.
   * @param string &$output
   *   (optional) A variable to assign the shell stdout to, passed by
   *   reference.
   * @param string &$error
   *   (optional) A variable to assign the shell stderr to, passed by
   *   reference.
   *
   * @return int|bool
   *   The operating system returned code, or FALSE if it was not possible to
   *   execute the command.
   */
  public function runOsShell(string $command, string $arguments, string $id, string &$output = NULL, string &$error = NULL): int;

}
