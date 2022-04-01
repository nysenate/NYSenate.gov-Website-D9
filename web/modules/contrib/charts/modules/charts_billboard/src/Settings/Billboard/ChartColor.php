<?php

namespace Drupal\charts_billboard\Settings\Billboard;

/**
 * Chart Color.
 */
class ChartColor implements \JsonSerializable {

  private $pattern = [];

  /**
   * Get Pattern.
   *
   * @return mixed
   *   Pattern.
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * Set Pattern.
   *
   * @param mixed $pattern
   *   Pattern.
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
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
