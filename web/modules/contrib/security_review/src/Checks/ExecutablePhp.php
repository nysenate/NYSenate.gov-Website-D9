<?php

namespace Drupal\security_review\Checks;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use GuzzleHttp\Exception\RequestException;

/**
 * Checks if PHP files written to the files directory can be executed.
 */
class ExecutablePhp extends Check {

  /**
   * Drupal's HTTP Client.
   *
   * @var \Drupal\Core\Http\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->httpClient = $this->container->get('http_client');
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Executable PHP';
  }

  /**
   * {@inheritdoc}
   */
  public function run($cli = FALSE) {
    global $base_url;
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Set up test file data.
    $message = 'Security review test ' . date('Ymdhis');
    $content = "<?php\necho '" . $message . "';";
    $file_path = PublicStream::basePath() . '/security_review_test.php';

    // Create the test file.
    if ($test_file = @fopen('./' . $file_path, 'w')) {
      fwrite($test_file, $content);
      fclose($test_file);
    }

    // Try to access the test file.
    try {
      $response = $this->httpClient->get($base_url . '/' . $file_path);
      if ($response->getStatusCode() == 200 && $response->getBody() === $message) {
        $result = CheckResult::FAIL;
        $findings[] = 'executable_php';
      }
    }
    catch (RequestException $e) {
      // Access was denied to the file.
    }

    // Remove the test file.
    if (file_exists('./' . $file_path)) {
      @unlink('./' . $file_path);
    }

    // Check for presence of the .htaccess file and if the contents are correct.
    $htaccess_path = PublicStream::basePath() . '/.htaccess';
    if (!file_exists($htaccess_path)) {
      $result = CheckResult::FAIL;
      $findings[] = 'missing_htaccess';
    }
    else {
      // Check whether the contents of .htaccess are correct.
      $contents = file_get_contents($htaccess_path);
      $expected = FileSecurity::htaccessLines(FALSE);

      // Trim each line separately then put them back together.
      $contents = implode("\n", array_map('trim', explode("\n", trim($contents))));
      $expected = implode("\n", array_map('trim', explode("\n", trim($expected))));

      if ($contents !== $expected) {
        $result = CheckResult::FAIL;
        $findings[] = 'incorrect_htaccess';
      }

      // Check whether .htaccess is writable.
      if (!$cli) {
        $writable_htaccess = is_writable($htaccess_path);
      }
      else {
        $writable = $this->security()->findWritableFiles([$htaccess_path], TRUE);
        $writable_htaccess = !empty($writable);
      }

      if ($writable_htaccess) {
        $findings[] = 'writable_htaccess';
        if ($result !== CheckResult::FAIL) {
          $result = CheckResult::WARN;
        }
      }
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function runCli() {
    return $this->run(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('The Drupal files directory is for user-uploaded files and by default provides some protection against a malicious user executing arbitrary PHP code against your site.');
    $paragraphs[] = $this->t('Read more about the <a href="https://drupal.org/node/615888">risk of PHP code execution on Drupal.org</a>.');

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Executable PHP in files directory'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $paragraphs = [];
    foreach ($result->findings() as $label) {
      switch ($label) {
        case 'executable_php':
          $paragraphs[] = $this->t('Security Review was able to execute a PHP file written to your files directory.');
          break;

        case 'missing_htaccess':
          $directory = PublicStream::basePath();
          $paragraphs[] = $this->t("The .htaccess file is missing from the files directory at @path", ['@path' => $directory]);
          $paragraphs[] = $this->t("Note, if you are using a webserver other than Apache you should consult your server's documentation on how to limit the execution of PHP scripts in this directory.");
          break;

        case 'incorrect_htaccess':
          $paragraphs[] = $this->t("The .htaccess file exists but does not contain the correct content. It is possible it's been maliciously altered.");
          break;

        case 'writable_htaccess':
          $paragraphs[] = $this->t("The .htaccess file is writable which poses a risk should a malicious user find a way to execute PHP code they could alter the .htaccess file to allow further PHP code execution.");
          break;
      }
    }

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $paragraphs = [];
    $directory = PublicStream::basePath();
    foreach ($result->findings() as $label) {
      switch ($label) {
        case 'executable_php':
          $paragraphs[] = $this->t('PHP file executed in @path', ['@path' => $directory]);
          break;

        case 'missing_htaccess':
          $paragraphs[] = $this->t('.htaccess is missing from @path', ['@path' => $directory]);
          break;

        case 'incorrect_htaccess':
          $paragraphs[] = $this->t('.htaccess wrong content');
          break;

        case 'writable_htaccess':
          $paragraphs[] = $this->t('.htaccess writable');
          break;
      }
    }

    return implode("\n", $paragraphs);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('PHP files in the Drupal files directory cannot be executed.');

      case CheckResult::FAIL:
        return $this->t('PHP files in the Drupal files directory can be executed.');

      case CheckResult::WARN:
        return $this->t('The .htaccess file in the files directory is writable.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
