<?php


namespace Drupal\charts\Settings;


class ChartsDefaultColors {

  protected $defaultColors = [
    '#2f7ed8',
    '#0d233a',
    '#8bbc21',
    '#910000',
    '#1aadce',
    '#492970',
    '#f28f43',
    '#77a1e5',
    '#c42525',
    '#a6c96a',
  ];

  /**
   * @return array
   */
  public function getDefaultColors() {
    return $this->defaultColors;
  }

  /**
   * @param array $defaultColors
   */
  public function setDefaultColors(array $defaultColors) {
    $this->defaultColors = $defaultColors;
  }

}
