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
   * @param int $timeout
   *   The timeout in seconds.
   *
   * @return $this
   */
  public function setTimeout(int $timeout): ImagemagickExecManagerInterface;

  /**
   * Gets the binaries package in use.
   *
   * @param string $package
   *   (optional) Force the graphics package suite.
   *
   * @return \Drupal\imagemagick\PackageSuite
   *   The package suite.
   */
  public function getPackageSuite(string $package = NULL): PackageSuite;

  /**
   * Gets the binaries package in use.
   *
   * @param string $package
   *   (optional) Force the graphics package.
   *
   * @return string
   *   The default package ('imagemagick'|'graphicsmagick'), or the $package
   *   argument.
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   Use ::getPackageSuite() instead.
   *
   * @see https://www.drupal.org/node/3409315
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
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   Use PackageSuite::label() instead.
   *
   * @see https://www.drupal.org/node/3409315
   */
  public function getPackageLabel(string $package = NULL): string;

  /**
   * Verifies file path of the executable binary by checking its version.
   *
   * @param string $path
   *   The user-submitted file path to the convert binary.
   * @param string|PackageSuite|null $package
   *   (optional) The graphics package to use.
   *
   * @return array
   *   An associative array containing:
   *   - output: The shell output of 'convert -version', if any.
   *   - errors: A list of error messages indicating if the executable could
   *     not be found or executed.
   */
  public function checkPath(string $path, string|PackageSuite|null $package = NULL): array;

  /**
   * Executes the convert executable as shell command.
   *
   * @param string|\Drupal\imagemagick\PackageCommand $command
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
  public function execute(string|PackageCommand $command, ImagemagickExecArguments $arguments, string &$output = NULL, string &$error = NULL, string $path = NULL): bool;

  /**
   * Executes a command on the operating system.
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
   * @return int
   *   The operating system returned code.
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   Use ::runProcess() instead.
   *
   * @see https://www.drupal.org/node/3414601
   */
  public function runOsShell(string $command, string $arguments, string $id, string &$output = NULL, string &$error = NULL): int;

  /**
   * Executes a command on the operating system, via Symfony Process.
   *
   * @param string[] $command
   *   The command to run and its arguments listed as separate entries.
   * @param string $id
   *   An identifier for the process to be spawned on the operating system.
   * @param string &$output
   *   (optional) A variable to assign the shell stdout to, passed by
   *   reference.
   * @param string &$error
   *   (optional) A variable to assign the shell stderr to, passed by
   *   reference.
   */
  public function runProcess(array $command, string $id, string &$output = NULL, string &$error = NULL): int;

}
