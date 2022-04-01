<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * X Axis Title.
 */
class XaxisTitle extends ChartTitle implements \JsonSerializable {

  private $text;

  /**
   * Get Text.
   *
   * @return string
   *   Text.
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Set Text.
   *
   * @param string $text
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
