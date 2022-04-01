<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Chart.
 */
class Chart implements \JsonSerializable {

  private $type;

  private $width = NULL;

  private $height = NULL;

  private $backgroundColor;

  private $polar = NULL;

  private $options3d;

  /**
   * Get Type.
   *
   * @return string
   *   Type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set Type.
   *
   * @param string $type
   *   Type.
   */
  public function setType($type = '') {
    $this->type = $type;
  }

  /**
   * Get Width.
   *
   * @return int|null
   *   Width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Set Width.
   *
   * @param int|null $width
   *   Width.
   */
  public function setWidth($width = NULL) {
    if (empty($width)) {
      $this->width = NULL;
    }
    else {
      $this->width = (int) $width;
    }
  }

  /**
   * Get Height.
   *
   * @return int|null
   *   Height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Set Height.
   *
   * @param int|null $height
   *   Height.
   */
  public function setHeight($height = NULL) {
    if (empty($height)) {
      $this->height = NULL;
    }
    else {
      $this->height = (int) $height;
    }
  }

  /**
   * Get BackgroundColor.
   *
   * @return string
   *   BackgroundColor.
   */
  public function getBackgroundColor() {
    return $this->backgroundColor;
  }

  /**
   * Set BackgroundColor.
   *
   * @param string $backgroundColor
   *   BackgroundColor.
   */
  public function setBackgroundColor($backgroundColor) {
    $this->backgroundColor = $backgroundColor;
  }

  /**
   * Get Polar
   *
   * @return bool
   *   Polar.
   */
  public function getPolar() {
    return $this->polar;
  }

  /**
   * Set Polar.
   *
   * @param bool $polar
   *   Polar.
   */
  public function setPolar($polar) {
    $this->polar = $polar;
  }

  /**
   * Get 3D options.
   *
   * @return mixed
   *   The 3D options.
   */
  public function getOptions3D() {
    return $this->options3d;
  }

  /**
   * Set 3D options.
   *
   * @param mixed
   *   The 3D options.
   */
  public function setOptions3D($options3d) {
    $this->options3d = $options3d;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Variables.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    if ($vars['type'] == 'pie' || $vars['type'] == 'donut') {
      unset($vars['x']);
    }

    return $vars;
  }

}
