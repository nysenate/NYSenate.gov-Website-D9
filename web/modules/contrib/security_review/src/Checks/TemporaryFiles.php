<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Check for sensitive temporary files like settings.php~.
 */
class TemporaryFiles extends Check {

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
    return 'Temporary files';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Get list of files from the site directory.
    $files = [];
    $site_path = $this->security()->sitePath() . '/';
    $dir = scandir($site_path);
    foreach ($dir as $file) {
      // Set full path to only files.
      if (!is_dir($file)) {
        $files[] = $site_path . $file;
      }
    }
    $this->moduleHandler()->alter('security_review_temporary_files', $files);

    // Analyze the files' names.
    foreach ($files as $path) {
      $matches = [];
      if (file_exists($path) && preg_match('/.*(~|\.sw[op]|\.bak|\.orig|\.save)$/', $path, $matches) !== FALSE && !empty($matches)) {
        // Found a temporary file.
        $findings[] = $path;
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("Some file editors create temporary copies of a file that can be left on the file system. A copy of a sensitive file like Drupal's settings.php may be readable by a malicious user who could use that information to further attack a site.");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Sensitive temporary files'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return [];
    }

    $paragraphs = [];
    $paragraphs[] = $this->t("The following are extraneous files in your Drupal installation that can probably be removed. You should confirm you have saved any of your work in the original files prior to removing these.");

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $findings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = $this->t('Temporary files:') . "\n";
    foreach ($findings as $file) {
      $output .= "\t" . $file . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('No sensitive temporary files were found.');

      case CheckResult::FAIL:
        return $this->t('Sensitive temporary files were found on your files system.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
