<?php

namespace Drupal\security_review_test;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * A test security check for testing extensibility.
 */
class Test extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review Test';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Test';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $findings = [];
    for ($i = 0; $i < 20; ++$i) {
      $findings[] = rand(0, 1) ? rand(0, 10) : 'string';
    }

    return $this->createResult(CheckResult::INFO, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    return 'The test ran.';
  }

}
