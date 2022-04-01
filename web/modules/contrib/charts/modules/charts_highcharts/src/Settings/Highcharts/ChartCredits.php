<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart Credits.
 */
class ChartCredits implements \JsonSerializable {

  private $enabled = FALSE;

  private $text;

  private $position;

  private $href = '#';

  /**
   * Is Enabled.
   *
   * @return bool
   *   Enabled.
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * Set Enabled.
   *
   * @param bool $enabled
   *   Enabled.
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
  }

  /**
   * @return mixed
   */
  public function getText() {
    return $this->text;
  }

  /**
   * @param mixed $text
   */
  public function setText($text) {
    $this->text = $text;
  }

  /**
   * @return mixed
   */
  public function getPosition() {
    return $this->position;
  }

  /**
   * @param mixed $position
   */
  public function setPosition($position) {
    $this->position = $position;
  }

  /**
   * @return mixed
   */
  public function getHref() {
    return $this->href;
  }

  /**
   * @param mixed $href
   */
  public function setHref($href) {
    $this->href = $href;
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
