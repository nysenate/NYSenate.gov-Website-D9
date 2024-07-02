<?php

namespace Drupal\password_policy;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Manipulates Password Policy Validation Report.
 *
 * @package Drupal\password_policy
 */
class PasswordPolicyValidationReport {

  /**
   * Validation errors.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Check to see if the report is invalid.
   *
   * @return bool
   *   True if the report is invalid (has errors), false if validated.
   */
  public function isInvalid() {
    if ($this->hasErrors()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Invalidate the report by adding an error.
   *
   * @param string $errorMessage
   *   Invalidation reason.
   */
  public function invalidate(string $errorMessage) {
    $this->errors[] = $errorMessage;
  }

  /**
   * Check to see if the report contains errors.
   *
   * @return bool
   *   True if there are errors, false if not.
   */
  public function hasErrors(): bool {
    return !empty($this->errors);
  }

  /**
   * Get the errors from the report.
   *
   * @return \Drupal\password_policy\TranslatableMarkup
   *   List of error messages.
   */
  public function getErrors(): TranslatableMarkup {
    $markupString = Markup::create(implode('<br/>', $this->errors));

    return new TranslatableMarkup('@message', ['@message' => $markupString]);
  }

}
