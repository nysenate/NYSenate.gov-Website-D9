<?php

namespace Drupal\security_review\Checks;

use Drupal\Core\Link;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\views\Entity\View;

/**
 * Checks for Views that do not check access.
 */
class ViewsAccess extends Check {

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
    return 'Views access';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If views is not enabled return with INFO.
    if (!$this->moduleHandler()->moduleExists('views')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = [];

    $views = View::loadMultiple();
    /** @var View[] $views */

    // Iterate through views and their displays.
    foreach ($views as $view) {
      if ($view->status()) {
        foreach ($view->get('display') as $display_name => $display) {
          $access = &$display['display_options']['access'];
          if (isset($access) && $access['type'] == 'none') {
            // Access is not controlled for this display.
            $findings[$view->id()][] = $display_name;
          }
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
    $paragraphs[] = $this->t("Views can check if the user is allowed access to the content. It is recommended that all Views implement some amount of access control, at a minimum checking for the permission 'access content'.");

    return [
      '#theme' => 'check_help',
      '#title' => $this->t('Views access'),
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
    $paragraphs[] = $this->t('The following View displays do not check access.');

    $items = [];
    foreach ($findings as $view_id => $displays) {
      $view = View::load($view_id);
      /** @var View $view */

      foreach ($displays as $display) {
        $items[] = Link::createFromRoute(
          $view->label() . ': ' . $display,
          'entity.view.edit_display_form',
          [
            'view' => $view_id,
            'display_id' => $display,
          ]
        );
      }
    }

    return [
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items,
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

    $output = $this->t('Views without access check:') . ":\n";
    foreach ($findings as $view_id => $displays) {
      $output .= "\t" . $view_id . ": " . implode(', ', $displays) . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Views are access controlled.');

      case CheckResult::FAIL:
        return $this->t('There are Views that do not provide any access checks.');

      case CheckResult::INFO:
        return $this->t('Module views is not enabled.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
