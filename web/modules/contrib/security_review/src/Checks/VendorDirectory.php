<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks the vendor directory is outside webroot.
 */
class VendorDirectory extends Check {

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
    return 'Vendor directory';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'vendor_directory_location';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $outside = TRUE;

    $autoloader = DRUPAL_ROOT . '/vendor/autoload.php';

    if (file_exists($autoloader)) {
      $result = CheckResult::FAIL;
      $outside = FALSE;
    }

    return $this->createResult($result, ['vendor_directory_location' => $outside]);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('A properly configured cron job executes, initiates, or manages a variety of tasks.');
    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Vendir directory is outside webroot.'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    if ($result->result() != CheckResult::FAIL) {
      return '';
    }

    return $this->t('Vendor directory location.');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Vendor directory is outside webroot.');

      case CheckResult::FAIL:
        return $this->t('Vendor directory is not outside webroot.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
