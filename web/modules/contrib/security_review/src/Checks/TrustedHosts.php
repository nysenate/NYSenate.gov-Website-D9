<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\TrustedHostSettings;

/**
 * Checks for base_url and trusted_host_patterns settings in settings.php.
 */
class TrustedHosts extends Check {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->settings = new TrustedHostSettings($this, $this->config);
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
    $base_url_set = FALSE;
    $trusted_host_patterns_set = FALSE;
    $findings = [];
    $settings_php = $this->security()->sitePath() . '/settings.php';

    if (!file_exists($settings_php)) {
      return $this->createResult(CheckResult::INFO, [], FALSE);
    }

    if ($this->settings()->get('method', 'token') === 'token') {
      // Use tokenization.
      $content = file_get_contents($settings_php);
      $tokens = token_get_all($content);

      $prev_settings_line = -1;
      foreach ($tokens as $token) {
        if (is_array($token)) {
          // Get information about the current token.
          $line = $token[2];
          $is_variable = $token[0] === T_VARIABLE;
          $is_string = $token[0] === T_CONSTANT_ENCAPSED_STRING;
          $is_settings = $is_variable ? $token[1] == '$settings' : FALSE;
          $is_base_url = $token[1] == '$base_url';
          $is_thp = trim($token[1], "\"'") == 'trusted_host_patterns';
          $is_after_settings = $line == $prev_settings_line;

          // Check for $base_url.
          if ($is_variable && $is_base_url) {
            $base_url_set = TRUE;
            $result = CheckResult::SUCCESS;
          }

          // Check for $settings['trusted_host_patterns'].
          if ($is_after_settings && $is_string && $is_thp) {
            $trusted_host_patterns_set = TRUE;
            $result = CheckResult::SUCCESS;
          }

          // If found both settings stop the review.
          if ($base_url_set && $trusted_host_patterns_set) {
            // Got everything we need.
            break;
          }

          // Store last $settings line.
          if ($is_settings) {
            $prev_settings_line = $line;
          }
        }
      }
    }
    else {
      // Use inclusion.
      include $settings_php;
      $base_url_set = isset($base_url);
      $trusted_host_patterns_set = isset($settings['trusted_host_patterns']);
    }

    if ($result === CheckResult::FAIL) {
      // Provide information if the check failed.
      global $base_url;
      $findings['base_url'] = $base_url;
      $findings['settings'] = $settings_php;
      $findings['base_url_set'] = $base_url_set;
      $findings['trusted_host_patterns_set'] = $trusted_host_patterns_set;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = [];
    $paragraphs[] = $this->t('Often Drupal needs to know the URL(s) it is responding from in order to build full links back to itself (e.g. password reset links sent via email). Until you explicitly tell Drupal what full or partial URL(s) it should respond for it must dynamically detect it based on the incoming request, something that can be malicously spoofed in order to trick someone into unknowningly visiting an attacker\'s site (known as a HTTP host header attack).');

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
    $paragraphs[] = $this->t('If the site should be available only at that URL it is recommended that you set it as the $base_url variable in the settings.php file at @file.', ['@file' => $settings_php]);
    $paragraphs[] = $this->t('If the site has multiple URLs it can respond from you should whitelist host patterns with trusted_host_patterns in settings.php.');
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
        return $this->t('Either $base_url or trusted_host_patterns is set.');

      case CheckResult::FAIL:
        return $this->t('Neither $base_url nor trusted_host_patterns is set.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
