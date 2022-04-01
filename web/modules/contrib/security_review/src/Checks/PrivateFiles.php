<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the private files' directory is under the web root.
 */
class PrivateFiles extends Check {

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
    return 'Private files';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $file_directory_path = PrivateStream::basePath();
    $visible = TRUE;
    if (empty($file_directory_path)) {
      // Private files feature is not enabled.
      $result = CheckResult::SUCCESS;
      $visible = FALSE;
    }
    elseif (strpos(realpath($file_directory_path), DRUPAL_ROOT) === 0) {
      // Path begins at root.
      $result = CheckResult::FAIL;
    }
    else {
      // The private files directory is placed correctly.
      $result = CheckResult::SUCCESS;
    }
    return $this->createResult($result, ['path' => $file_directory_path], $visible);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("If you have Drupal's private files feature enabled you should move the files directory outside of the web server's document root. Drupal will secure access to files that it renders the link to, but if a user knows the actual system path they can circumvent Drupal's private files feature. You can protect against this by specifying a files directory outside of the webserver root.");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Private files'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() != CheckResult::FAIL) {
      return [];
    }

    $paragraphs = [];
    $paragraphs[] = $this->t('Your files directory is not outside of the server root.');
    $paragraphs[] = Link::createFromRoute(
      $this->t('Edit the files directory path.'),
      'system.file_system_settings'
    );

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
    if ($result->result() != CheckResult::FAIL) {
      return '';
    }

    return $this->t('Private files directory: @path', ['@path' => $result->findings()['path']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Private files directory is outside the web server root.');

      case CheckResult::FAIL:
        return $this->t('Private files is enabled but the specified directory is not secure outside the web server root.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
