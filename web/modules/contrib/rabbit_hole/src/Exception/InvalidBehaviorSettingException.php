<?php

namespace Drupal\rabbit_hole\Exception;

/**
 * Exception for the case of invalid behavior settings.
 */
class InvalidBehaviorSettingException extends \Exception {

  /**
   * Rabbit Hole setting name.
   *
   * @var string
   */
  private $setting;

  /**
   * InvalidBehaviorSettingException constructor.
   *
   * @param string $setting
   *   Rabbit Hole setting name.
   */
  public function __construct($setting) {
    parent::__construct();
    $this->setting = $setting;
  }

  /**
   * Returns invalid setting name.
   */
  public function getSetting() {
    return $this->setting();
  }

}
