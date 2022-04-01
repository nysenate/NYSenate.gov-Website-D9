<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Data.
 */
class ChartData implements \JsonSerializable {

  private $columns = [];

  private $type;

  private $labels = TRUE;

  private $x = 'x';

  private $groups = '';

  private $xs;

  /**
   * Get X.
   *
   * @return mixed
   *   X.
   */
  public function getX() {
    return $this->x;
  }

  /**
   * Set X.
   *
   * @param mixed $x
   *   X.
   */
  public function setX($x) {
    $this->x = $x;
  }

  /**
   * Get Columns.
   *
   * @return array
   *   Columns.
   */
  public function getColumns() {
    return $this->columns;
  }

  /**
   * Set Columns.
   *
   * @param mixed $columns
   *   Columns.
   */
  public function setColumns($columns) {
    $this->columns = $columns;
  }

  /**
   * Get Type.
   *
   * @return mixed
   *   Type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set Type.
   *
   * @param mixed $type
   *   Type.
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * Get Labels.
   *
   * @return mixed
   *   Labels.
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * Set Labels.
   *
   * @param mixed $labels
   *   Labels.
   */
  public function setLabels($labels) {
    $this->labels = $labels;
  }

  /**
   * Get Stacking.
   *
   * @return mixed
   *   Stacking.
   */
  public function getGroups() {
    return $this->groups;
  }

  /**
   * Set Stacking.
   *
   * @param array $groups
   *   Stacking.
   */
  public function setGroups($groups) {
    $this->groups = $groups;
  }

  /**
   * @return mixed
   */
  public function getXs() {
    return $this->xs;
  }

  /**
   * @param mixed $xs
   */
  public function setXs($xs) {
    $this->xs = $xs;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    if ($vars['type'] == 'pie' || $vars['type'] == 'donut') {
      unset($vars['x']);
    }

    return $vars;
  }


}
