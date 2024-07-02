<?php

namespace Drupal\imagemagick;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Manage execution of ImageMagick/GraphicsMagick commands.
 */
class ImagemagickExecManager implements ImagemagickExecManagerInterface {

  use StringTranslationTrait;

  /**
   * Replacement for percentage while escaping.
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   There is no need to escape arguments any more.
   *
   * @see https://www.drupal.org/node/3414601
   */
  const PERCENTAGE_REPLACE = '1357902468IMAGEMAGICKPERCENTSIGNPATTERN8642097531';

  /**
   * Whether we are running on Windows OS.
   */
  protected bool $isWindows;

  /**
   * The execution timeout.
   */
  protected int $timeout = 60;

  /**
   * Constructs an ImagemagickExecManager object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param string $appRoot
   *   The app root.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\imagemagick\ImagemagickFormatMapperInterface $formatMapper
   *   The format mapper service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly string $appRoot,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ImagemagickFormatMapperInterface $formatMapper,
    protected readonly MessengerInterface $messenger,
  ) {
    $this->isWindows = substr(PHP_OS, 0, 3) === 'WIN';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatMapper(): ImagemagickFormatMapperInterface {
    return $this->formatMapper;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeout(int $timeout): ImagemagickExecManagerInterface {
    $this->timeout = $timeout;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageSuite(string $package = NULL): PackageSuite {
    if ($package === NULL) {
      $package = $this->configFactory->get('imagemagick.settings')->get('binaries');
    }
    return PackageSuite::from($package);
  }

  /**
   * {@inheritdoc}
   */
  public function getPackage(string $package = NULL): string {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::getPackageSuite() instead. See https://www.drupal.org/node/3409315', E_USER_DEPRECATED);
    if ($package === NULL) {
      $package = $this->configFactory->get('imagemagick.settings')->get('binaries');
    }
    return $package;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageLabel(string $package = NULL): string {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use PackageSuite::label() instead. See https://www.drupal.org/node/3409315', E_USER_DEPRECATED);
    if ($package === NULL) {
      $package = $this->configFactory->get('imagemagick.settings')->get('binaries');
    }
    $packageSuite = PackageSuite::tryFrom($package);
    return $packageSuite ? $packageSuite->label() : $package;
  }

  /**
   * {@inheritdoc}
   */
  public function checkPath(string $path, string|PackageSuite|null $packageSuite = NULL): array {
    if (is_string($packageSuite)) {
      @trigger_error('Passing a string value for $packageSuite in ' . __METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use PackageSuite instead. See https://www.drupal.org/node/3409315', E_USER_DEPRECATED);
      $packageSuite = PackageSuite::from($packageSuite);
    }

    $status = [
      'output' => '',
      'errors' => [],
    ];

    // Execute gm or convert based on settings.
    $packageSuite = $packageSuite ?: $this->getPackageSuite();
    $binary = match ($packageSuite) {
      PackageSuite::Imagemagick => 'convert',
      PackageSuite::Graphicsmagick => 'gm',
    };
    $executable = $this->getExecutable($binary, $path);

    // If a path is given, we check whether the binary exists and can be
    // invoked.
    if (!empty($path)) {
      // Check whether the given file exists.
      if (!is_file($executable)) {
        $status['errors'][] = $this->t('The @suite executable %file does not exist.', [
          '@suite' => $packageSuite->label(),
          '%file' => $executable,
        ]);
      }
      // If it exists, check whether we can execute it.
      elseif (!is_executable($executable)) {
        $status['errors'][] = $this->t('The @suite file %file is not executable.', [
          '@suite' => $packageSuite->label(),
          '%file' => $executable,
        ]);
      }
    }

    // In case of errors, check for open_basedir restrictions.
    if ($status['errors'] && ($open_basedir = ini_get('open_basedir'))) {
      $status['errors'][] = $this->t('The PHP <a href=":php-url">open_basedir</a> security restriction is set to %open-basedir, which may prevent to locate the @suite executable.', [
        '@suite' => $packageSuite->label(),
        '%open-basedir' => $open_basedir,
        ':php-url' => 'http://php.net/manual/en/ini.core.php#ini.open-basedir',
      ]);
    }

    // Unless we had errors so far, try to invoke convert.
    if (!$status['errors']) {
      $error = NULL;
      $this->runProcess([$executable, '-version'], $packageSuite->value, $status['output'], $error);
      if ($error !== '') {
        $status['errors'][] = $error;
      }
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(string|PackageCommand $command, ImagemagickExecArguments $arguments, string &$output = NULL, string &$error = NULL, string $path = NULL): bool {
    if (is_string($command)) {
      @trigger_error('Passing a string value for $command in ' . __METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use PackageCommand instead. See https://www.drupal.org/node/3409315', E_USER_DEPRECATED);
      $command = PackageCommand::tryFrom($command);
    }
    $packageSuite = $this->getPackageSuite();

    $cmdline = [];

    $binary = match ($packageSuite) {
      PackageSuite::Imagemagick => $command->value,
      PackageSuite::Graphicsmagick => 'gm',
    };

    $cmd = $this->getExecutable($binary, $path);
    $cmdline[] = $cmd;

    if ($source_path = $arguments->getSourceLocalPath()) {
      if (($source_frames = $arguments->getSourceFrames()) !== NULL) {
        $source_path .= $source_frames;
      }
    }

    if ($destination_path = $arguments->getDestinationLocalPath()) {
      // If the format of the derivative image has to be changed, concatenate
      // the new image format and the destination path, delimited by a colon.
      // @see http://www.imagemagick.org/script/command-line-processing.php#output
      if (($format = $arguments->getDestinationFormat()) !== '') {
        $destination_path = $format . ':' . $destination_path;
      }
    }

    if ($command === PackageCommand::Identify) {
      // ImageMagick syntax: identify [arguments] source.
      // GraphicsMagick syntax: gm identify [arguments] source.
      if ($packageSuite === PackageSuite::Graphicsmagick) {
        $cmdline[] = 'identify';
      }
      array_push($cmdline, ...$arguments->toArray(ArgumentMode::PreSource));
      $cmdline[] = $source_path;
    }
    elseif ($command === PackageCommand::Convert) {
      $args = match ($packageSuite) {
        PackageSuite::Imagemagick => $this->buildImagemagickConvertCommand($arguments, $source_path, $destination_path),
        PackageSuite::Graphicsmagick => $this->buildGraphicsmagickConvertCommand($arguments, $source_path, $destination_path),
      };
      array_push($cmdline, ...$args);
    }

    $return_code = $this->runProcess($cmdline, $packageSuite->value, $output, $error);

    if ($return_code !== FALSE) {
      // If the executable returned a non-zero code, log to the watchdog.
      if ($return_code != 0) {
        if ($error === '') {
          // If there is no error message, and allowed in config, log a
          // warning.
          if ($this->configFactory->get('imagemagick.settings')->get('log_warnings') === TRUE) {
            $this->logger->warning("@suite returned with code @code [command: @command @cmdline]", [
              '@suite' => $this->getPackageSuite()->label(),
              '@code' => $return_code,
              '@command' => $cmd,
              '@cmdline' => '[' . implode('] [', $cmdline) . ']',
            ]);
          }
        }
        else {
          // Log $error with context information.
          $this->logger->error("@suite error @code: @error [command: @command @cmdline]", [
            '@suite' => $this->getPackageSuite()->label(),
            '@code' => $return_code,
            '@error' => $error,
            '@command' => $cmd,
            '@cmdline' => '[' . implode('] [', $cmdline) . ']',
          ]);
        }
        // Executable exited with an error code, return FALSE.
        return FALSE;
      }

      // The shell command was executed successfully.
      return TRUE;
    }
    // The shell command could not be executed.
    return FALSE;
  }

  /**
   * Builds a convert command for Imagemagick.
   *
   * ImageMagick syntax: convert input [arguments] output.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string $sourcePath
   *   The source image file path.
   * @param string $destinationPath
   *   The destination image file path.
   *
   * @return string[]
   *   The command to be executed.
   *
   * @see http://www.imagemagick.org/Usage/basics/#cmdline
   */
  private function buildImagemagickConvertCommand(ImagemagickExecArguments $arguments, string $sourcePath, string $destinationPath): array {
    $cmdline = [];
    if (($pre = $arguments->toArray(ArgumentMode::PreSource)) !== []) {
      array_push($cmdline, ...$pre);
    }
    if ($sourcePath) {
      $cmdline[] = $sourcePath;
    }
    array_push($cmdline, ...$arguments->toArray(ArgumentMode::PostSource));
    $cmdline[] = $destinationPath;
    return $cmdline;
  }

  /**
   * Builds a convert command for Graphicsmagick.
   *
   * GraphicsMagick syntax: gm convert [arguments] input output.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   An ImageMagick execution arguments object.
   * @param string $sourcePath
   *   The source image file path.
   * @param string $destinationPath
   *   The destination image file path.
   *
   * @return string[]
   *   The command to be executed.
   *
   * @see http://www.graphicsmagick.org/GraphicsMagick.html
   */
  private function buildGraphicsmagickConvertCommand(ImagemagickExecArguments $arguments, string $sourcePath, string $destinationPath): array {
    $cmdline = ['convert'];
    if (($pre = $arguments->toArray(ArgumentMode::PreSource)) !== []) {
      array_push($cmdline, ...$pre);
    }
    array_push($cmdline, ...$arguments->toArray(ArgumentMode::PostSource));
    if ($sourcePath) {
      $cmdline[] = $sourcePath;
    }
    $cmdline[] = $destinationPath;
    return $cmdline;
  }

  /**
   * {@inheritdoc}
   */
  public function runOsShell(string $command, string $arguments, string $id, string &$output = NULL, string &$error = NULL): int {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. Use ::runProcess() instead. See https://www.drupal.org/node/3414601', E_USER_DEPRECATED);
    $command_line = $command . ' ' . $arguments;
    $output = '';
    $error = '';

    Timer::start('imagemagick:runOsShell');
    $process = Process::fromShellCommandline($command_line, $this->appRoot);
    $process->setTimeout($this->timeout);
    try {
      $process->run();
      $output = $process->getOutput();
      $error = $process->getErrorOutput();
      $return_code = $process->getExitCode();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
      $return_code = $process->getExitCode() ? $process->getExitCode() : 1;
    }
    $execution_time = Timer::stop('imagemagick:runOsShell')['time'];

    // Process debugging information if required.
    $packageSuite = PackageSuite::tryFrom($id);
    if ($this->configFactory->get('imagemagick.settings')->get('debug')) {
      $this->debugMessage('@suite command: <pre>@raw</pre> executed in @execution_timems', [
        '@suite' => $packageSuite ? $packageSuite->label() : $id,
        '@raw' => print_r($command_line, TRUE),
        '@execution_time' => $execution_time,
      ]);
      if ($output !== '') {
        $this->debugMessage('@suite output: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@raw' => print_r($output, TRUE),
        ]);
      }
      if ($error !== '') {
        $this->debugMessage('@suite error @return_code: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@return_code' => $return_code,
          '@raw' => print_r($error, TRUE),
        ]);
      }
    }

    return $return_code;
  }

  /**
   * {@inheritdoc}
   */
  public function runProcess(array $command, string $id, string &$output = NULL, string &$error = NULL): int {
    $command_line = '[' . implode('] [', $command) . ']';
    $output = '';
    $error = '';

    Timer::start('imagemagick:runOsShell');
    $process = new Process($command, $this->appRoot);
    $process->setTimeout($this->timeout);
    try {
      $process->run();
      $output = $process->getOutput();
      $error = $process->getErrorOutput();
      $return_code = $process->getExitCode();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
      $return_code = $process->getExitCode() ? $process->getExitCode() : 1;
    }
    $execution_time = Timer::stop('imagemagick:runOsShell')['time'];

    // Process debugging information if required.
    if ($this->configFactory->get('imagemagick.settings')->get('debug')) {
      $packageSuite = PackageSuite::tryFrom($id);
      $this->debugMessage('@suite command: <pre>@raw</pre> executed in @execution_timems', [
        '@suite' => $packageSuite ? $packageSuite->label() : $id,
        '@raw' => $command_line,
        '@execution_time' => $execution_time,
      ]);
      if ($output !== '') {
        $this->debugMessage('@suite output: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@raw' => print_r($output, TRUE),
        ]);
      }
      if ($error !== '') {
        $this->debugMessage('@suite error @return_code: <pre>@raw</pre>', [
          '@suite' => $packageSuite ? $packageSuite->label() : $id,
          '@return_code' => $return_code,
          '@raw' => print_r($error, TRUE),
        ]);
      }
    }

    return $return_code;
  }

  /**
   * Logs a debug message, and shows it on the screen for authorized users.
   *
   * @param string $message
   *   The debug message.
   * @param string[] $context
   *   Context information.
   */
  public function debugMessage(string $message, array $context) {
    $this->logger->debug($message, $context);
    if ($this->currentUser->hasPermission('administer site configuration')) {
      // Strips raw text longer than 10 lines to optimize displaying.
      if (isset($context['@raw'])) {
        $raw = explode("\n", $context['@raw']);
        if (count($raw) > 10) {
          $tmp = [];
          for ($i = 0; $i < 9; $i++) {
            $tmp[] = $raw[$i];
          }
          $tmp[] = (string) $this->t('[Further text stripped. The watchdog log has the full text.]');
          $context['@raw'] = implode("\n", $tmp);
        }
      }
      // @codingStandardsIgnoreLine
      $this->messenger->addMessage($this->t($message, $context), 'status', TRUE);
    }
  }

  /**
   * Gets the list of locales installed on the server.
   *
   * @return string
   *   The string resulting from the execution of 'locale -a' in *nix systems.
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   There is no replacement.
   *
   * @see https://www.drupal.org/node/3415326
   */
  public function getInstalledLocales(): string {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. There is no replacement. See https://www.drupal.org/node/3415326', E_USER_DEPRECATED);
    $output = '';
    if ($this->isWindows === FALSE) {
      $this->runProcess(['locale', '-a'], 'locale', $output);
    }
    else {
      $output = (string) $this->t("List not available on Windows servers.");
    }
    return $output;
  }

  /**
   * Returns the full path to the executable.
   *
   * @param string $binary
   *   The program to execute, typically 'convert', 'identify' or 'gm'.
   * @param string $path
   *   (optional) A custom path to the folder of the executable. When left
   *   empty, the setting imagemagick.settings.path_to_binaries is taken.
   *
   * @return string
   *   The full path to the executable.
   */
  protected function getExecutable(string $binary, string $path = NULL): string {
    // $path is only passed from the validation of the image toolkit form, on
    // which the path to convert is configured. @see ::checkPath()
    if (!isset($path)) {
      $path = $this->configFactory->get('imagemagick.settings')->get('path_to_binaries');
    }

    $executable = $binary;
    if ($this->isWindows) {
      $executable .= '.exe';
    }

    return $path . $executable;
  }

  /**
   * Escapes a string.
   *
   * PHP escapeshellarg() drops non-ascii characters, this is a replacement.
   *
   * Code below is copied from Symfony Process' escapeArgument() method.
   *
   * @param string $argument
   *   The string to escape.
   *
   * @return string
   *   An escaped string for use in the ::execute method.
   *
   * @deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0.
   *   There is no need to escape arguments any more.
   *
   * @see https://www.drupal.org/node/3414601
   */
  public function escapeShellArg(string $argument): string {
    @trigger_error(__METHOD__ . '() is deprecated in imagemagick:8.x-3.7 and is removed from imagemagick:4.0.0. There is no need to escape arguments any more. See https://www.drupal.org/node/3414601', E_USER_DEPRECATED);
    if ('' === $argument) {
      return '""';
    }
    if ('\\' !== \DIRECTORY_SEPARATOR) {
      return "'" . str_replace("'", "'\\''", $argument) . "'";
    }
    if (str_contains($argument, "\0")) {
      $argument = str_replace("\0", '?', $argument);
    }
    if (!preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
      return $argument;
    }
    $argument = preg_replace('/(\\\\+)$/', '$1$1', $argument);

    return '"' . str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument) . '"';
  }

}
