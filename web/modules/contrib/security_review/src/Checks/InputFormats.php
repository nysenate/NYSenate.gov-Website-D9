<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks for vulnerabilities related to input formats.
 *
 * Checks for formats that either do not have HTML filter that can be used by
 * untrusted users, or if they do check if unsafe tags are allowed.
 */
class InputFormats extends Check {

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
    return 'Text formats';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'input_formats';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If filter is not enabled return with INFO.
    if (!$this->moduleHandler()->moduleExists('filter')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = [];

    $formats = filter_formats();
    $untrusted_roles = $this->security()->untrustedRoles();
    $unsafe_tags = $this->security()->unsafeTags();

    foreach ($formats as $format) {
      $format_roles = array_keys(filter_get_roles_by_format($format));
      $intersect = array_intersect($format_roles, $untrusted_roles);

      if (!empty($intersect)) {
        // Untrusted users can use this format.
        // Check format for enabled HTML filter.
        $filter_html_enabled = FALSE;
        if ($format->filters()->has('filter_html')) {
          $filter_html_enabled = $format->filters('filter_html')
            ->getConfiguration()['status'];
        }
        $filter_html_escape_enabled = FALSE;
        if ($format->filters()->has('filter_html_escape')) {
          $filter_html_escape_enabled = $format->filters('filter_html_escape')
            ->getConfiguration()['status'];
        }

        if ($filter_html_enabled) {
          $filter = $format->filters('filter_html');

          // Check for unsafe tags in allowed tags.
          $allowed_tags = array_keys($filter->getHTMLRestrictions()['allowed']);
          foreach (array_intersect($allowed_tags, $unsafe_tags) as $tag) {
            // Found an unsafe tag.
            $findings['tags'][$format->id()] = $tag;
          }
        }
        elseif (!$filter_html_escape_enabled) {
          // Format is usable by untrusted users but does not contain the HTML
          // Filter or the HTML escape.
          $findings['formats'][$format->id()] = $format->label();
        }
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
    $paragraphs[] = $this->t("Certain HTML tags can allow an attacker to take control of your site. Drupal's input format system makes use of a set filters to run on incoming text. The 'HTML Filter' strips out harmful tags and Javascript events and should be used on all formats accessible by untrusted users.");
    $paragraphs[] = new Link(
      $this->t("Read more about Drupal's input formats in the handbooks."),
      Url::fromUri('http://drupal.org/node/224921')
    );

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Allowed HTML tags in text formats'),
      '#paragraphs' => $paragraphs,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $output = [];

    if (!empty($result->findings()['tags'])) {
      $paragraphs = [];
      $paragraphs[] = Link::createFromRoute(
        $this->t('Review your text formats.'),
        'filter.admin_overview'
      );
      $paragraphs[] = $this->t('It is recommended you remove the following tags from roles accessible by untrusted users.');
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $result->findings()['tags'],
      ];
    }

    if (!empty($result->findings()['formats'])) {
      $paragraphs = [];
      $paragraphs[] = $this->t('The following formats are usable by untrusted roles and do not filter or escape allowed HTML tags.');
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $result->findings()['formats'],
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $output = '';

    if (!empty($result->findings()['tags'])) {
      $output .= $this->t('Tags') . "\n";
      foreach ($result->findings()['tags'] as $tag) {
        $output .= "\t$tag\n";
      }
    }

    if (!empty($result->findings()['formats'])) {
      $output .= $this->t('Formats') . "\n";
      foreach ($result->findings()['formats'] as $format) {
        $output .= "\t$format\n";
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Untrusted users are not allowed to input dangerous HTML tags.');

      case CheckResult::FAIL:
        return $this->t('Untrusted users are allowed to input dangerous HTML tags.');

      case CheckResult::INFO:
        return $this->t('Module filter is not enabled.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
