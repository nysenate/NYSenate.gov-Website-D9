<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Title.
 */
class ChartTitle implements \JsonSerializable {

  private $text;

  /**
   * Get Text.
   *
   * @return mixed
   *   Text.
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Set Text.
   *
   * @param mixed $text
   *   Text.
   */
  public function setText($text) {
    $this->text = $text;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
