<?php


namespace Drupal\charts\Settings;


class ChartsDefaultSettings {

  protected $colors;

  /**
   * ChartsDefaultSettings constructor.
   */
  public function __construct() {
    $this->colors = new ChartsDefaultColors();
  }

  public $defaults = [
    'type' => 'line',
    'library' => NULL,
    'grouping' => FALSE,
    'label_field' => NULL,
    'data_fields' => NULL,
    'field_colors' => NULL,
    'title' => '',
    'title_position' => 'out',
    'data_labels' => FALSE,
    'data_markers' => TRUE,
    'legend' => TRUE,
    'legend_position' => 'right',
    'background' => '',
    'three_dimensional' => FALSE,
    'polar' => FALSE,
    'tooltips' => TRUE,
    'tooltips_use_html' => FALSE,
    'width' => NULL,
    'width_units' => '%',
    'height' => NULL,
    'height_units' => 'px',
    'xaxis_title' => '',
    'xaxis_labels_rotation' => 0,
    'yaxis_title' => '',
    'yaxis_min' => '',
    'yaxis_max' => '',
    'yaxis_prefix' => '',
    'yaxis_suffix' => '',
    'yaxis_decimal_count' => '',
    'yaxis_labels_rotation' => 0,
    'green_to' => 100,
    'green_from' => 85,
    'yellow_to' => 85,
    'yellow_from' => 50,
    'red_to' => 50,
    'red_from' => 0,
    'max' => 100,
    'min' => 0,
  ];

  /**
   * @return array
   */
  public function getDefaults() {
    $defaults = $this->defaults;
    $defaults['colors'] = $this->colors->getDefaultColors();

    return $defaults;
  }

  /**
   * @param array $defaults
   */
  public function setDefaults(array $defaults) {
    $this->defaults = $defaults;
  }

}
