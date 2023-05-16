<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks for trusted_host_patterns in settings.php.
 */
class TrustedHosts extends Check {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
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
    return 'Trusted hosts';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::FAIL;
    $trusted_host_patterns_set = FALSE;
    $findings = [];
    $settings_php = $this->security()->sitePath() . '/settings.php';

    if (!file_exists($settings_php)) {
      return $this->createResult(CheckResult::INFO, [], FALSE);
    }

    if (!empty(Settings::get('trusted_host_patterns'))) {
      $trusted_host_patterns_set = TRUE;
      $result = CheckResult::SUCCESS;
    }

    if ($result === CheckResult::FAIL) {
      // Provide information if the check failed.
      $findings['settings'] = $settings_php;
      $findings['trusted_host_patterns_set'] = $trusted_host_patterns_set;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t("Often Drupal needs to know the URL(s) it is responding from in order to build full links back to itself (e.g. password reset links sent via email). Until you explicitly tell Drupal what full or partial URL(s) it should respond for it must dynamically detect it based on the incoming request, something that can be malicously spoofed in order to trick someone into unknowningly visiting an attacker's site (known as a HTTP host header attack).");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Drupal trusted hosts'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    global $base_url;
    if ($result->result() !== CheckResult::FAIL) {
      return [];
    }

    $settings_php = $this->security()->sitePath() . '/settings.php';

    $paragraphs = [];
    $paragraphs[] = $this->t('This site is responding from the URL: :url.', [':url' => $base_url]);
    $paragraphs[] = $this->t('If the site has multiple URLs it can respond from you should whitelist host patterns with trusted_host_patterns in settings.php at @file.', ['@file' => $settings_php]);
    $paragraphs[] = new Link($this->t('Read more about HTTP Host Header attacks and setting trusted_host_patterns.'), Url::fromUri('https://www.drupal.org/node/1992030'));

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Trusted hosts are set.');

      case CheckResult::FAIL:
        return $this->t('Trusted hosts are not set.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
