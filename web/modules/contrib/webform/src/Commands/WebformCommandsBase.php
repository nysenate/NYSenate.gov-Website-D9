<?php
// phpcs:ignoreFile

namespace Drupal\webform\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Drush\Exec\ExecTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for Webform commands for Drush 9.x.
 */
abstract class WebformCommandsBase extends DrushCommands {

  /**
   * The webform CLI service.
   *
   * @var \Drupal\webform\Commands\WebformCliServiceInterface
   */
  protected $cliService;

  /**
   * Constructs a WebformCommandsBase object.
   *
   * @param \Drupal\webform\Commands\WebformCliServiceInterface $cli_service
   *   The webform CLI service.
   */
  public function __construct(WebformCliServiceInterface $cli_service) {
    $this->cliService = $cli_service;

    // Injecting the WebformCommand into the CLI service so that calls to
    // drush functions can be delegatef back the below methods.
    // @see \Drupal\webform\Commands\WebformCliService::__call
    $this->cliService->setCommand($this);
  }

  public function drush_confirm($question) {
    return $this->io()->confirm($question);
  }

  public function drush_choice($choices, $msg, $default = NULL) {
    return $this->io()->choice($msg, $choices, $default);
  }

  public function drush_log($message, $type = LogLevel::INFO) {
    $this->logger()->log($type, $message);
  }

  public function drush_print($message) {
    $this->output()->writeln($message);
  }

  public function drush_get_option($name) {
    return $this->input()->getOption($name);
  }

  public function drush_user_abort() {
    throw new UserAbortException();
  }

  public function drush_set_error($error) {
    throw new \Exception($error);
  }

  public function drush_redispatch_get_options() {
    return Drush::redispatchOptions();
  }

  public function drush_download_file($url, $destination) {
    $destination_tmp = drush_tempnam('download_file');
    \Drupal::httpClient()->get($url, ['sink' => $destination_tmp]);
    if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
      @file_put_contents($destination_tmp, $file);
    }
    if (!drush_file_not_empty($destination_tmp)) {
      // Download failed.
      throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
    }
    if ($destination) {
      $fs = new Filesystem();
      $fs->rename($destination_tmp, $destination, TRUE);
      return $destination;
    }
    return $destination_tmp;
  }

  public function drush_move_dir($src, $dest) {
    $fs = new Filesystem();
    $fs->rename($src, $dest, TRUE);
    return TRUE;
  }

  public function drush_mkdir($path) {
    $fs = new Filesystem();
    $fs->mkdir($path);
    return TRUE;
  }

  public function drush_tarball_extract($path, $destination = FALSE) {
    $this->drush_mkdir($destination);
    $cwd = getcwd();
    if (preg_match('/\.tgz$/', $path)) {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['tar', '-xvzf', $path, '-C', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, $process->getOutput()), ['!filename' => $path]));
      }
    }
    else {
      drush_op('chdir', dirname($path));
      $process = Drush::process(['unzip', $path, '-d', $destination]);
      $process->run();
      $return = $process->isSuccessful();
      drush_op('chdir', $cwd);

      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, $process->getOutput()), ['!filename' => $path]));
      }
    }
    return $return;
  }

}
